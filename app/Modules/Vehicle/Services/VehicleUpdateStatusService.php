<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Services;

use App\Modules\Vehicle\Commands\UpdateStatusVehicleCommand;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;

class VehicleUpdateStatusService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
        private readonly VehicleFindService $findService,
    ) {}

    public function execute(string $id, UpdateStatusVehicleCommand $command, ?string $companyId = null): Vehicle
    {
        $vehicle = $this->findService->execute($id, $companyId);

        $this->repository->updateStatus($vehicle, $command);

        return $vehicle->refresh();
    }
}
