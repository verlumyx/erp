<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Services;

use App\Modules\MeasurementUnit\Commands\SearchMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;

class MeasurementUnitSearchService
{
    public function __construct(
        private readonly MeasurementUnitRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchMeasurementUnitCommand $command): array
    {
        return $this->repository->search($command);
    }
}
