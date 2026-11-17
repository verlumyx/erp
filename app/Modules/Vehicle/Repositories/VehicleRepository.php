<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Repositories;

use App\Modules\Vehicle\Commands\CreateVehicleCommand;
use App\Modules\Vehicle\Commands\SearchVehicleCommand;
use App\Modules\Vehicle\Commands\UpdateStatusVehicleCommand;
use App\Modules\Vehicle\Commands\UpdateVehicleCommand;
use App\Modules\Vehicle\Exceptions\VehicleNotFoundException;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;

class VehicleRepository extends VehicleFilters implements VehicleRepositoryInterface
{
    public function create(CreateVehicleCommand $command): Vehicle
    {
        return Vehicle::create([
            'id' => $command->id,
            'company_id' => $command->companyId,
            'code' => $this->generateNextCode($command->companyId),
            'plate' => $command->plate,
            'name' => $command->name,
            'brand' => $command->brand,
            'model' => $command->model,
            'year' => $command->year,
            'vehicle_type' => $command->vehicleType,
            'capacity_weight' => $command->capacityWeight,
            'capacity_volume' => $command->capacityVolume,
            'notes' => $command->notes,
            'status' => 'active',
            'created_by' => $command->createdBy,
        ]);
    }

    public function findById(string $id, ?string $companyId = null): ?Vehicle
    {
        return Vehicle::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['company', 'creator'])
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Vehicle
    {
        $vehicle = $this->findById($id, $companyId);

        if (! $vehicle) {
            throw new VehicleNotFoundException;
        }

        return $vehicle;
    }

    public function findByPlate(string $plate, ?string $companyId = null, ?string $exceptId = null): ?Vehicle
    {
        return Vehicle::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('plate', $plate)
            ->first();
    }

    public function update(Vehicle $model, UpdateVehicleCommand $command): void
    {
        $model->update([
            'plate' => $command->plate,
            'name' => $command->name,
            'brand' => $command->brand,
            'model' => $command->model,
            'year' => $command->year,
            'vehicle_type' => $command->vehicleType,
            'capacity_weight' => $command->capacityWeight,
            'capacity_volume' => $command->capacityVolume,
            'notes' => $command->notes,
        ]);
    }

    public function updateStatus(Vehicle $model, UpdateStatusVehicleCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    public function search(SearchVehicleCommand $command): array
    {
        $query = Vehicle::query()
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId))
            ->with(['creator']);

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
     * Genera el siguiente código correlativo por empresa (CAM000001, CAM000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Vehicle::query()
            ->where('company_id', $companyId)
            ->where('code', 'ilike', Vehicle::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Vehicle::CODE_PREFIX))) + 1
            : 1;

        return Vehicle::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
