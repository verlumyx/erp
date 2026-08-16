<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Services;

use App\Modules\MeasurementUnit\Exceptions\MeasurementUnitNotFoundException;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;

class MeasurementUnitFindService
{
    public function __construct(
        private readonly MeasurementUnitRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): MeasurementUnit
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new MeasurementUnitNotFoundException;
        }

        return $model;
    }
}
