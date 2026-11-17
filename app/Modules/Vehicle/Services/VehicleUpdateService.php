<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Services;

use App\Modules\Vehicle\Commands\UpdateVehicleCommand;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;

class VehicleUpdateService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
        private readonly VehicleFindService $findService,
    ) {}

    public function execute(string $id, UpdateVehicleCommand $command, ?string $companyId = null): Vehicle
    {
        $vehicle = $this->findService->execute($id, $companyId);

        $this->repository->update($vehicle, $command);

        return $vehicle->refresh();
    }
}
