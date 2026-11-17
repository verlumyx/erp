<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Services;

use App\Modules\Vehicle\Commands\SearchVehicleCommand;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;

class VehicleSearchService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: array<int, Vehicle>, total: int }
     */
    public function execute(SearchVehicleCommand $command): array
    {
        return $this->repository->search($command);
    }
}
