<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Repositories;

use App\Modules\WarehouseLocation\Commands\CreateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Commands\UpdateStatusWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Commands\UpdateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WarehouseLocationRepository extends WarehouseLocationFilters implements WarehouseLocationRepositoryInterface
{
    public function create(CreateWarehouseLocationCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            if ($command->isDefault === 'yes') {
                $this->clearDefault($command->warehouseId);
            }

            WarehouseLocation::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'warehouse_id' => $command->warehouseId,
                'parent_id' => $command->parentId,
                'name' => $command->name,
                'location_code' => $command->locationCode,
                'type' => $command->type,
                'capacity' => $command->capacity,
                'is_default' => $command->isDefault,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?WarehouseLocation
    {
        return WarehouseLocation::query()
            ->with(['warehouse', 'parent'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): WarehouseLocation
    {
        return WarehouseLocation::query()
            ->with(['warehouse', 'parent'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(WarehouseLocation $model, UpdateWarehouseLocationCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            if ($command->isDefault === 'yes') {
                $this->clearDefault($model->warehouse_id, $model->id);
            }

            $model->update([
                'parent_id' => $command->parentId,
                'name' => $command->name,
                'location_code' => $command->locationCode,
                'type' => $command->type,
                'capacity' => $command->capacity,
                'is_default' => $command->isDefault,
            ]);
        });
    }

    public function updateStatus(WarehouseLocation $model, UpdateStatusWarehouseLocationCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * Solo se listan las ubicaciones de bodegas que gestionan ubicaciones: la
     * "Principal" de una bodega con uses_locations = 'no' no se muestra en la UI.
     *
     * @return array{ data: WarehouseLocation[], total: int }
     */
    public function search(SearchWarehouseLocationCommand $command): array
    {
        $query = WarehouseLocation::query()
            ->with(['warehouse', 'parent'])
            ->whereHas('warehouse', fn (Builder $q) => $q->where('uses_locations', 'yes'))
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('warehouse_id')
            ->orderByDesc('is_default')
            ->orderBy('location_code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Leave a single default location per warehouse.
     */
    private function clearDefault(string $warehouseId, ?string $exceptId = null): void
    {
        WarehouseLocation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('is_default', 'yes')
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->update(['is_default' => 'no']);
    }

    /**
     * Generate the next sequential per-company code (UBI000001, UBI000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = WarehouseLocation::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', WarehouseLocation::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(WarehouseLocation::CODE_PREFIX))) + 1
            : 1;

        return WarehouseLocation::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
