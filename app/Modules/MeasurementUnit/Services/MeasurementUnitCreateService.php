<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Services;

use App\Modules\MeasurementUnit\Commands\CreateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;

class MeasurementUnitCreateService
{
    public function __construct(
        private readonly MeasurementUnitRepositoryInterface $repository,
    ) {}

    public function execute(CreateMeasurementUnitCommand $command): MeasurementUnit
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
