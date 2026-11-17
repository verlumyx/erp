<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Services;

use App\Modules\Vehicle\Commands\SearchVehicleCommand;
use App\Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;

class VehicleOptionSearchService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: array<int, \App\Modules\Vehicle\Models\Vehicle>, total: int }
     */
    public function execute(SearchVehicleCommand $command): array
    {
        return $this->repository->search($command);
    }
}
