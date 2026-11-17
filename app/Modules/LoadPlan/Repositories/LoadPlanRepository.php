<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Repositories;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\LoadPlan\Commands\CreateLoadPlanCommand;
use App\Modules\LoadPlan\Commands\LoadPlanDispatchData;
use App\Modules\LoadPlan\Commands\SearchLoadPlanCommand;
use App\Modules\LoadPlan\Commands\UpdateLoadPlanCommand;
use App\Modules\LoadPlan\Commands\UpdateStatusLoadPlanCommand;
use App\Modules\LoadPlan\Exceptions\LoadPlanNotFoundException;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Models\LoadPlanDispatch;
use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoadPlanRepository extends LoadPlanFilters implements LoadPlanRepositoryInterface
{
    public function create(CreateLoadPlanCommand $command): LoadPlan
    {
        return DB::transaction(function () use ($command): LoadPlan {
            $loadPlan = LoadPlan::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'plan_date' => $command->planDate,
                'route_id' => $command->routeId,
                'warehouse_id' => $command->warehouseId,
                'vehicle_id' => $command->vehicleId,
                'driver_name' => $command->driverName,
                'driver_id' => $command->driverId,
                'helper_name' => $command->helperName,
                'description' => $command->description,
                'total_dispatches' => 0,
                'total_weight' => 0,
                'total_volume' => 0,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncDispatches($loadPlan, $command->dispatches);

            return $loadPlan->refresh();
        });
    }

    public function findById(string $id, ?string $companyId = null): ?LoadPlan
    {
        $plan = LoadPlan::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with([
                'route',
                'warehouse',
                'vehicle',
                'driver',
                'creator',
                'planDispatches' => fn ($q) => $q->where('status', 'active')->orderBy('sequence'),
                'planDispatches.dispatch',
                'planDispatches.dispatch.recipient',
                'planDispatches.dispatch.sourceable',
                'planDispatches.dispatch.clientAddress',
            ])
            ->find($id);

        if (! $plan) {
            return null;
        }

        $dispatchIds = $plan->planDispatches->pluck('dispatch_id')->filter()->all();
        if (! empty($dispatchIds)) {
            $orderIds = $plan->planDispatches
                ->map(fn ($pd) => $pd->dispatch)
                ->filter(fn ($d) => $d && $d->sourceable_type === 'sales_order')
                ->pluck('sourceable_id')
                ->filter()
                ->all();

            $invoicesByDispatch = DB::table('app_sales_invoices')
                ->where('company_id', $plan->company_id)
                ->whereIn('dispatch_id', $dispatchIds)
                ->pluck('code', 'dispatch_id')
                ->all();

            $invoicesByOrder = empty($orderIds) ? [] : DB::table('app_sales_invoices')
                ->where('company_id', $plan->company_id)
                ->where('sourceable_type', 'sales_order')
                ->whereIn('sourceable_id', $orderIds)
                ->pluck('code', 'sourceable_id')
                ->all();

            foreach ($plan->planDispatches as $pd) {
                $d = $pd->dispatch;
                if (! $d) {
                    continue;
                }

                if ($d->sourceable_type === 'sales_invoice') {
                    $pd->invoice_code = $d->sourceable?->code;
                } else {
                    $pd->invoice_code = $invoicesByDispatch[$d->id] ?? ($invoicesByOrder[$d->sourceable_id] ?? null);
                }
                $pd->order_code = $d->sourceable_type === 'sales_order' ? $d->sourceable?->code : null;
            }
        }

        return $plan;
    }

    public function findOrFail(string $id, ?string $companyId = null): LoadPlan
    {
        $loadPlan = $this->findById($id, $companyId);

        if (! $loadPlan) {
            throw new LoadPlanNotFoundException;
        }

        return $loadPlan;
    }

    public function update(LoadPlan $model, UpdateLoadPlanCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            $model->update([
                'plan_date' => $command->planDate,
                'route_id' => $command->routeId,
                'warehouse_id' => $command->warehouseId,
                'vehicle_id' => $command->vehicleId,
                'driver_name' => $command->driverName,
                'driver_id' => $command->driverId,
                'helper_name' => $command->helperName,
                'description' => $command->description,
            ]);

            $this->syncDispatches($model, $command->dispatches);
        });
    }

    public function updateStatus(LoadPlan $model, UpdateStatusLoadPlanCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    public function search(SearchLoadPlanCommand $command): array
    {
        $query = LoadPlan::query()
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId))
            ->with(['route', 'warehouse', 'vehicle', 'creator']);

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query
            ->orderBy('created_at', 'desc')
            ->offset($command->offset)
            ->limit($command->limit)
            ->get()
            ->all();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    /**
     * @param  array<int, LoadPlanDispatchData>  $dispatches
     */
    public function syncDispatches(LoadPlan $model, array $dispatches): void
    {
        $existing = LoadPlanDispatch::query()
            ->where('load_plan_id', $model->id)
            ->get()
            ->keyBy('dispatch_id');

        $activeDispatchIds = [];

        foreach ($dispatches as $index => $item) {
            $dispatchId = $item->dispatchId;
            $activeDispatchIds[] = $dispatchId;

            if ($existing->has($dispatchId)) {
                $row = $existing->get($dispatchId);
                $row->update([
                    'sequence' => $item->sequence ?: ($index + 1),
                    'status' => 'active',
                ]);
            } else {
                LoadPlanDispatch::create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $model->company_id,
                    'load_plan_id' => $model->id,
                    'dispatch_id' => $dispatchId,
                    'sequence' => $item->sequence ?: ($index + 1),
                    'status' => 'active',
                ]);
            }
        }

        // Deactivate dispatches that were removed
        LoadPlanDispatch::query()
            ->where('load_plan_id', $model->id)
            ->whereNotIn('dispatch_id', $activeDispatchIds)
            ->update(['status' => 'inactive']);

        // Recalculate totals from active dispatches
        $activeDispatches = Dispatch::query()
            ->whereIn('id', $activeDispatchIds)
            ->get();

        $totalWeight = $activeDispatches->sum(fn ($d) => (float) $d->total_weight);
        $totalVolume = $activeDispatches->sum(fn ($d) => (float) $d->total_volume);

        $model->update([
            'total_dispatches' => count($activeDispatchIds),
            'total_weight' => round($totalWeight, 4),
            'total_volume' => round($totalVolume, 4),
        ]);
    }

    public function pendingDispatches(string $companyId, ?string $routeId = null, ?string $excludePlanId = null): array
    {
        // Find dispatch IDs already assigned to an active load plan
        $alreadyAssignedQuery = DB::table('app_load_plan_dispatches')
            ->join('app_load_plans', 'app_load_plans.id', '=', 'app_load_plan_dispatches.load_plan_id')
            ->where('app_load_plan_dispatches.company_id', $companyId)
            ->where('app_load_plan_dispatches.status', 'active')
            ->whereIn('app_load_plans.status', ['draft', 'in_transit']);

        if ($excludePlanId) {
            $alreadyAssignedQuery->where('app_load_plans.id', '!=', $excludePlanId);
        }

        $assignedDispatchIds = $alreadyAssignedQuery->pluck('app_load_plan_dispatches.dispatch_id')->all();

        $dispatches = Dispatch::query()
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->whereNotIn('id', $assignedDispatchIds)
            ->when($routeId, function ($query) use ($routeId): void {
                $routeClientIds = DB::table('app_route_clients')
                    ->where('route_id', $routeId)
                    ->where('status', 'active')
                    ->pluck('client_id')
                    ->filter()
                    ->all();

                $query->where(function ($q) use ($routeId, $routeClientIds): void {
                    if (! empty($routeClientIds)) {
                        $q->where(function ($sq) use ($routeClientIds): void {
                            $sq->where('recipient_type', 'client')
                                ->whereIn('recipient_id', $routeClientIds);
                        })->orWhere('route_id', $routeId);
                    } else {
                        $q->where('route_id', $routeId);
                    }
                });
            })
            ->with(['recipient', 'clientAddress', 'deliveryRoute', 'sourceable'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($dispatches->isEmpty()) {
            return [];
        }

        $dispatchIds = $dispatches->pluck('id')->all();
        $orderIds = $dispatches
            ->where('sourceable_type', 'sales_order')
            ->pluck('sourceable_id')
            ->filter()
            ->all();

        // Find invoice codes linked by dispatch_id
        $invoicesByDispatch = DB::table('app_sales_invoices')
            ->where('company_id', $companyId)
            ->whereIn('dispatch_id', $dispatchIds)
            ->pluck('code', 'dispatch_id')
            ->all();

        // Find invoice codes linked by sales_order sourceable
        $invoicesByOrder = empty($orderIds) ? [] : DB::table('app_sales_invoices')
            ->where('company_id', $companyId)
            ->where('sourceable_type', 'sales_order')
            ->whereIn('sourceable_id', $orderIds)
            ->pluck('code', 'sourceable_id')
            ->all();

        return $dispatches->map(function (Dispatch $d) use ($invoicesByDispatch, $invoicesByOrder): array {
            $invoiceCode = null;

            if ($d->sourceable_type === 'sales_invoice') {
                $invoiceCode = $d->sourceable?->code;
            } else {
                $invoiceCode = $invoicesByDispatch[$d->id] ?? ($invoicesByOrder[$d->sourceable_id] ?? null);
            }

            $orderCode = $d->sourceable_type === 'sales_order' ? $d->sourceable?->code : null;

            $recipientName = $d->recipient?->name ?? '—';
            $recipientCode = $d->recipient?->code ?? null;

            return [
                'id' => $d->id,
                'code' => $d->code,
                'dispatch_date' => $d->dispatch_date?->toDateString(),
                'route_id' => $d->route_id,
                'route_name' => $d->deliveryRoute?->name,
                'recipient_name' => $recipientName,
                'recipient_code' => $recipientCode,
                'address' => $d->clientAddress?->address ?? $d->clientAddress?->name ?? '—',
                'order_code' => $orderCode,
                'invoice_code' => $invoiceCode,
                'total_quantity' => (float) $d->total_quantity,
                'total_weight' => (float) $d->total_weight,
                'total_volume' => (float) $d->total_volume,
                'status' => $d->status,
            ];
        })->all();
    }

    private function generateNextCode(string $companyId): string
    {
        $last = LoadPlan::query()
            ->where('company_id', $companyId)
            ->where('code', 'ilike', LoadPlan::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(LoadPlan::CODE_PREFIX))) + 1
            : 1;

        return LoadPlan::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
