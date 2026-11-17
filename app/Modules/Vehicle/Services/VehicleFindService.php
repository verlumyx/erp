<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Services;

use App\Modules\Vehicle\Exceptions\VehicleNotFoundException;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;

class VehicleFindService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Vehicle
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new VehicleNotFoundException;
        }

        return $model;
    }
}
