<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Services;

use App\Modules\MeasurementUnit\Commands\UpdateStatusMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Exceptions\MeasurementUnitNotFoundException;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;

class MeasurementUnitUpdateStatusService
{
    public function __construct(
        private readonly MeasurementUnitRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusMeasurementUnitCommand $command, ?string $companyId = null): MeasurementUnit
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new MeasurementUnitNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
