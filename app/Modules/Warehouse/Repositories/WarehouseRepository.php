<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories;

use App\Modules\Warehouse\Commands\CreateWarehouseCommand;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Commands\UpdateStatusWarehouseCommand;
use App\Modules\Warehouse\Commands\UpdateWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Support\Facades\DB;

class WarehouseRepository extends WarehouseFilters implements WarehouseRepositoryInterface
{
    public function create(CreateWarehouseCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            if ($command->isDefault === 'yes') {
                $this->clearDefault($command->companyId);
            }

            Warehouse::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'name' => $command->name,
                'type' => $command->type,
                'address' => $command->address,
                'phone' => $command->phone,
                'city' => $command->city,
                'responsible_user_id' => $command->responsibleUserId,
                'is_default' => $command->isDefault,
                'allows_negative_stock' => $command->allowsNegativeStock,
                'uses_locations' => $command->usesLocations,
                'is_sales_available' => $command->isSalesAvailable,
                'notes' => $command->notes,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Warehouse
    {
        return Warehouse::query()
            ->with(['responsible', 'defaultLocation'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Warehouse
    {
        return Warehouse::query()
            ->with(['responsible', 'defaultLocation'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Warehouse $model, UpdateWarehouseCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            if ($command->isDefault === 'yes') {
                $this->clearDefault($model->company_id, $model->id);
            }

            $model->update([
                'name' => $command->name,
                'type' => $command->type,
                'address' => $command->address,
                'phone' => $command->phone,
                'city' => $command->city,
                'responsible_user_id' => $command->responsibleUserId,
                'is_default' => $command->isDefault,
                'allows_negative_stock' => $command->allowsNegativeStock,
                'uses_locations' => $command->usesLocations,
                'is_sales_available' => $command->isSalesAvailable,
                'notes' => $command->notes,
            ]);
        });
    }

    public function updateStatus(Warehouse $model, UpdateStatusWarehouseCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: Warehouse[], total: int }
     */
    public function search(SearchWarehouseCommand $command): array
    {
        $query = Warehouse::query()
            ->with('responsible')
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('is_default')
            ->orderBy('name')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Leave a single default warehouse per company.
     */
    private function clearDefault(?string $companyId, ?string $exceptId = null): void
    {
        Warehouse::query()
            ->where('company_id', $companyId)
            ->where('is_default', 'yes')
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->update(['is_default' => 'no']);
    }

    /**
     * Generate the next sequential per-company code (BOD000001, BOD000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Warehouse::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Warehouse::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Warehouse::CODE_PREFIX))) + 1
            : 1;

        return Warehouse::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
