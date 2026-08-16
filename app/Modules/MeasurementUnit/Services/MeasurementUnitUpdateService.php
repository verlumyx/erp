<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Services;

use App\Modules\MeasurementUnit\Commands\UpdateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Exceptions\MeasurementUnitNotFoundException;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;

class MeasurementUnitUpdateService
{
    public function __construct(
        private readonly MeasurementUnitRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateMeasurementUnitCommand $command, ?string $companyId = null): MeasurementUnit
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new MeasurementUnitNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
