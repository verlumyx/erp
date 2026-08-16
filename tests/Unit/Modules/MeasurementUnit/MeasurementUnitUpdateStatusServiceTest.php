<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Commands\UpdateStatusMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Exceptions\MeasurementUnitNotFoundException;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use App\Modules\MeasurementUnit\Services\MeasurementUnitUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the status and returns the refreshed model', function () {
    $command = new UpdateStatusMeasurementUnitCommand(status: 'inactive');
    $model = new MeasurementUnit(['status' => 'active']);

    $repository = Mockery::mock(MeasurementUnitRepositoryInterface::class);
    $repository->expects('findById')->with('unit-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('unit-uuid', null)
        ->andReturn(new MeasurementUnit(['status' => 'inactive']));

    $service = new MeasurementUnitUpdateStatusService($repository);

    expect($service->execute('unit-uuid', $command)->status)->toBe('inactive');
});

test('it throws when changing the status of a missing measurement unit', function () {
    $command = new UpdateStatusMeasurementUnitCommand(status: 'inactive');

    $repository = Mockery::mock(MeasurementUnitRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new MeasurementUnitUpdateStatusService($repository);

    $service->execute('missing-uuid', $command);
})->throws(MeasurementUnitNotFoundException::class);
