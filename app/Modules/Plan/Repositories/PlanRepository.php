<?php

declare(strict_types=1);

namespace App\Modules\Plan\Repositories;

use App\Modules\Plan\Commands\CreatePlanCommand;
use App\Modules\Plan\Commands\SearchPlanCommand;
use App\Modules\Plan\Commands\UpdatePlanCommand;
use App\Modules\Plan\Commands\UpdateStatusPlanCommand;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PlanRepository extends PlanFilters implements PlanRepositoryInterface
{
    public function create(CreatePlanCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            Plan::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'service_id' => $command->serviceId,
                'name' => $command->name,
                'capacity' => $command->capacity,
                'duration_days' => $command->durationDays,
                'sale_price' => $command->salePrice,
                'roi_target_pct' => $command->roiTargetPct,
                'active' => true,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Plan
    {
        return Plan::query()
            ->with('service')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Plan
    {
        return Plan::query()
            ->with('service')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Plan $model, UpdatePlanCommand $command): void
    {
        $model->update([
            'service_id' => $command->serviceId,
            'name' => $command->name,
            'capacity' => $command->capacity,
            'duration_days' => $command->durationDays,
            'sale_price' => $command->salePrice,
            'roi_target_pct' => $command->roiTargetPct,
        ]);
    }

    public function updateStatus(Plan $model, UpdateStatusPlanCommand $command): void
    {
        $model->update([
            'active' => $command->active,
        ]);
    }

    /**
     * @return array{ data: Plan[], total: int }
     */
    public function search(SearchPlanCommand $command): array
    {
        $query = Plan::query()
            ->with('service')
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('created_at')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Generate the next sequential per-company code (PLA000001, PLA000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Plan::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Plan::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Plan::CODE_PREFIX))) + 1
            : 1;

        return Plan::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
