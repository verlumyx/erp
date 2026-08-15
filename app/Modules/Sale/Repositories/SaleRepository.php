<?php

declare(strict_types=1);

namespace App\Modules\Sale\Repositories;

use App\Modules\Account\Models\Profile;
use App\Modules\Plan\Models\Plan;
use App\Modules\Sale\Commands\CancelSaleCommand;
use App\Modules\Sale\Commands\CreateSaleCommand;
use App\Modules\Sale\Commands\ReactivateSaleCommand;
use App\Modules\Sale\Commands\RenewSaleCommand;
use App\Modules\Sale\Commands\SearchSaleCommand;
use App\Modules\Sale\Exceptions\ProfilesUnavailableException;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleProfile;
use App\Modules\Sale\Models\SaleRenewal;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;
use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleRepository extends SaleFilters implements SaleRepositoryInterface
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function create(CreateSaleCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            /** @var Plan $plan */
            $plan = Plan::query()
                ->where('id', $command->planId)
                ->where('company_id', $command->companyId)
                ->firstOrFail();

            // Re-verificación de disponibilidad con lock para blindar contra carreras.
            $this->lockAndAssertAvailable($command->profileIds);

            $durationDays = (int) $plan->duration_days;
            $price = (float) $plan->sale_price;
            $endDate = Carbon::parse($command->startDate)->addDays($durationDays)->toDateString();

            $sale = Sale::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_id' => $command->clientId,
                'plan_id' => $plan->id,
                'agent_id' => $command->agentId,
                'service_id' => $plan->service_id,
                'capacity' => $plan->capacity,
                'duration_days' => $durationDays,
                'price' => $price,
                'start_date' => $command->startDate,
                'end_date' => $endDate,
                'status' => Sale::STATUS_ACTIVE,
                'notes' => $command->notes,
            ]);

            $this->assignProfiles($sale, $command->profileIds);

            $this->recordSaleTransaction($sale);
        });
    }

    public function renew(Sale $model, RenewSaleCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            $durationDays = $command->durationDays ?? (int) $model->duration_days;
            $price = $command->price ?? (float) $model->price;

            $previousEndDate = $model->end_date->toDateString();
            $newEndDate = $model->end_date->copy()->addDays($durationDays)->toDateString();

            SaleRenewal::create([
                'id' => $command->id,
                'sale_id' => $model->id,
                'renewed_at' => now()->toDateString(),
                'previous_end_date' => $previousEndDate,
                'new_end_date' => $newEndDate,
                'duration_days' => $durationDays,
                'price' => $price,
                'renewed_by' => $command->renewedBy,
                'notes' => $command->notes,
            ]);

            $model->update([
                'end_date' => $newEndDate,
                'status' => Sale::STATUS_ACTIVE,
            ]);

            $this->recordRenewalTransaction($model, $price, "Renovación de venta {$model->code}");
        });
    }

    public function reactivate(Sale $model, ReactivateSaleCommand $command): void
    {
        $profileIds = $command->profileIds !== []
            ? $command->profileIds
            : $model->saleProfiles()->pluck('profile_id')->map('strval')->all();

        DB::transaction(function () use ($model, $command, $profileIds): void {
            $this->lockAndAssertAvailable($profileIds);

            // Reasignar profiles: limpiar la asignación previa y crear la nueva.
            $model->saleProfiles()->delete();
            $this->assignProfiles($model, $profileIds);

            $durationDays = $command->durationDays ?? (int) $model->duration_days;
            $price = $command->price ?? (float) $model->price;

            $previousEndDate = $model->end_date->toDateString();
            $newEndDate = now()->addDays($durationDays)->toDateString();

            SaleRenewal::create([
                'id' => $command->id,
                'sale_id' => $model->id,
                'renewed_at' => now()->toDateString(),
                'previous_end_date' => $previousEndDate,
                'new_end_date' => $newEndDate,
                'duration_days' => $durationDays,
                'price' => $price,
                'renewed_by' => $command->renewedBy,
                'notes' => $command->notes,
            ]);

            $model->update([
                'end_date' => $newEndDate,
                'price' => $price,
                'duration_days' => $durationDays,
                'status' => Sale::STATUS_ACTIVE,
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);

            $this->recordRenewalTransaction($model, $price, "Reactivación de venta {$model->code}");
        });
    }

    public function cancel(Sale $model, CancelSaleCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            $profileIds = $model->saleProfiles()->pluck('profile_id')->all();

            $model->update([
                'status' => Sale::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $command->cancellationReason,
            ]);

            if ($profileIds !== []) {
                Profile::query()
                    ->whereIn('id', $profileIds)
                    ->update(['status' => 'available']);
            }
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Sale
    {
        return Sale::query()
            ->with($this->showRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Sale
    {
        return Sale::query()
            ->with($this->showRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * @return array{ data: Sale[], total: int }
     */
    public function search(SearchSaleCommand $command): array
    {
        $query = Sale::query()
            ->with(['client', 'plan', 'service', 'agent'])
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
     * Listado del reporte de vencimientos: ventas ordenadas por vencimiento
     * ascendente (las más urgentes primero) según los filtros del command.
     *
     * @return array{ data: Sale[], total: int }
     */
    public function searchExpirations(SearchSaleCommand $command): array
    {
        $query = Sale::query()
            ->with(['client', 'plan', 'service', 'agent'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('end_date')
            ->orderBy('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Métricas del reporte de vencimientos. La ventana "por vencer" mira hacia
     * adelante (expiring_from/expiring_to); la base de vencidas y la tasa de
     * renovación usan la ventana explícita (date_from/date_to) si se indica, o
     * todo el histórico en caso contrario.
     *
     * @return array{ expiring_count: int, expiring_amount: float, expired_count: int, expired_amount: float, renewal_rate: float }
     */
    public function summarizeExpirations(SearchSaleCommand $command): array
    {
        $filters = $command->filters;
        $companyId = $command->companyId;
        $serviceId = $filters['service_id'] ?? null;
        $agentId = $filters['agent_id'] ?? null;
        $expiringFrom = $filters['expiring_from'] ?? null;
        $expiringTo = $filters['expiring_to'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $scoped = fn () => Sale::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->when($agentId, fn ($q) => $q->where('agent_id', $agentId));

        $expiring = $scoped()
            ->where('status', Sale::STATUS_ACTIVE)
            ->when($expiringFrom, fn ($q) => $q->whereDate('end_date', '>=', $expiringFrom))
            ->when($expiringTo, fn ($q) => $q->whereDate('end_date', '<=', $expiringTo));

        $expired = $scoped()
            ->where('status', Sale::STATUS_EXPIRED)
            ->when($dateFrom, fn ($q) => $q->whereDate('end_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('end_date', '<=', $dateTo));

        $expiringCount = (clone $expiring)->count();
        $expiringAmount = (float) (clone $expiring)->sum('price');
        $expiredCount = (clone $expired)->count();
        $expiredAmount = (float) (clone $expired)->sum('price');

        $renewedCount = SaleRenewal::query()
            ->whereHas('sale', fn ($q) => $q
                ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId))
                ->when($serviceId, fn ($qq) => $qq->where('service_id', $serviceId))
                ->when($agentId, fn ($qq) => $qq->where('agent_id', $agentId)))
            ->when($dateFrom, fn ($q) => $q->whereDate('previous_end_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('previous_end_date', '<=', $dateTo))
            ->distinct('sale_id')
            ->count('sale_id');

        $base = $renewedCount + $expiredCount;
        $renewalRate = $base > 0 ? round($renewedCount / $base * 100, 2) : 0.0;

        return [
            'expiring_count' => $expiringCount,
            'expiring_amount' => $expiringAmount,
            'expired_count' => $expiredCount,
            'expired_amount' => $expiredAmount,
            'renewal_rate' => $renewalRate,
        ];
    }

    /**
     * Bloquea los profiles indicados y verifica que sigan disponibles. Si alguno
     * ya no lo está, aborta con 409 listando los profiles no disponibles.
     *
     * @param  array<int, string>  $profileIds
     */
    private function lockAndAssertAvailable(array $profileIds): void
    {
        if ($profileIds === []) {
            return;
        }

        $profiles = Profile::query()
            ->with('account:id,email')
            ->whereIn('id', $profileIds)
            ->lockForUpdate()
            ->get();

        $unavailable = $profiles
            ->filter(fn (Profile $profile): bool => $profile->status !== 'available')
            ->map(fn (Profile $profile): array => [
                'id' => $profile->id,
                'label' => ($profile->account?->email ?? '—')." · Perfil {$profile->number}",
            ])
            ->values()
            ->all();

        if ($unavailable !== []) {
            throw new ProfilesUnavailableException($unavailable);
        }
    }

    /**
     * Crea las filas de sale_profiles y marca los profiles como 'occupied'.
     *
     * @param  array<int, string>  $profileIds
     */
    private function assignProfiles(Sale $sale, array $profileIds): void
    {
        foreach ($profileIds as $profileId) {
            SaleProfile::create([
                'id' => Str::uuid7()->toString(),
                'sale_id' => $sale->id,
                'profile_id' => $profileId,
            ]);
        }

        Profile::query()
            ->whereIn('id', $profileIds)
            ->update(['status' => 'occupied']);
    }

    private function recordSaleTransaction(Sale $sale): void
    {
        $serviceName = $sale->service()->value('name') ?? 'servicio';
        $clientName = $sale->client()->value('name') ?? 'cliente';

        $this->transactionRepository->create(new CreateTransactionCommand(
            id: Str::uuid7()->toString(),
            companyId: $sale->company_id,
            type: Transaction::TYPE_INCOME,
            category: 'sale',
            amount: (float) $sale->price,
            date: $sale->start_date->toDateString(),
            paymentMethod: 'cash',
            description: "Venta {$serviceName} a {$clientName}",
            recordedBy: $sale->agent_id,
            relatedType: 'Sale',
            relatedId: $sale->id,
            periodFrom: $sale->start_date->toDateString(),
            periodTo: $sale->end_date->toDateString(),
        ));
    }

    private function recordRenewalTransaction(Sale $sale, float $price, string $description): void
    {
        $this->transactionRepository->create(new CreateTransactionCommand(
            id: Str::uuid7()->toString(),
            companyId: $sale->company_id,
            type: Transaction::TYPE_INCOME,
            category: 'renewal',
            amount: $price,
            date: now()->toDateString(),
            paymentMethod: 'cash',
            description: $description,
            recordedBy: $sale->agent_id,
            relatedType: 'Sale',
            relatedId: $sale->id,
            periodFrom: now()->toDateString(),
            periodTo: $sale->end_date->toDateString(),
        ));
    }

    /**
     * Relaciones cargadas para la pantalla de detalle de la venta.
     *
     * @return array<int|string, mixed>
     */
    private function showRelations(): array
    {
        return [
            'client',
            'plan',
            'service',
            'agent',
            'saleProfiles' => fn ($q) => $q->orderBy('created_at'),
            'saleProfiles.profile.account',
            'renewals' => fn ($q) => $q->orderByDesc('renewed_at')->orderByDesc('created_at'),
            'transactions' => fn ($q) => $q->orderByDesc('date')->orderByDesc('created_at'),
        ];
    }

    /**
     * Genera el siguiente código secuencial por compañía (SAL000001, SAL000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Sale::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Sale::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Sale::CODE_PREFIX))) + 1
            : 1;

        return Sale::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
