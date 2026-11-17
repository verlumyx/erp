<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Repositories\Contracts;

use App\Modules\Vehicle\Commands\CreateVehicleCommand;
use App\Modules\Vehicle\Commands\SearchVehicleCommand;
use App\Modules\Vehicle\Commands\UpdateStatusVehicleCommand;
use App\Modules\Vehicle\Commands\UpdateVehicleCommand;
use App\Modules\Vehicle\Models\Vehicle;

interface VehicleRepositoryInterface
{
    public function create(CreateVehicleCommand $command): Vehicle;

    public function findById(string $id, ?string $companyId = null): ?Vehicle;

    public function findOrFail(string $id, ?string $companyId = null): Vehicle;

    public function findByPlate(string $plate, ?string $companyId = null, ?string $exceptId = null): ?Vehicle;

    public function update(Vehicle $model, UpdateVehicleCommand $command): void;

    public function updateStatus(Vehicle $model, UpdateStatusVehicleCommand $command): void;

    /** @return array{ data: array<int, Vehicle>, total: int } */
    public function search(SearchVehicleCommand $command): array;
}
