<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Repositories\Contracts;

use App\Modules\MeasurementUnit\Commands\CreateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Commands\SearchMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Commands\UpdateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Commands\UpdateStatusMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;

interface MeasurementUnitRepositoryInterface
{
    public function create(CreateMeasurementUnitCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?MeasurementUnit;

    public function findOrFail(string $id, ?string $companyId = null): MeasurementUnit;

    public function update(MeasurementUnit $model, UpdateMeasurementUnitCommand $command): void;

    public function updateStatus(MeasurementUnit $model, UpdateStatusMeasurementUnitCommand $command): void;

    /** @return array{ data: MeasurementUnit[], total: int } */
    public function search(SearchMeasurementUnitCommand $command): array;
}
