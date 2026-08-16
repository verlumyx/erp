<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Commands\UpdateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Exceptions\MeasurementUnitNotFoundException;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use App\Modules\MeasurementUnit\Services\MeasurementUnitUpdateService;

uses(Tests\TestCase::class);

test('it updates the measurement unit and returns the refreshed model', function () {
    $command = new UpdateMeasurementUnitCommand(name: 'Caja', abbreviation: 'cja');
    $model = new MeasurementUnit(['name' => 'Cajita', 'abbreviation' => 'cja']);

    $repository = Mockery::mock(MeasurementUnitRepositoryInterface::class);
    $repository->expects('findById')->with('unit-uuid', null)->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('unit-uuid', null)
        ->andReturn(new MeasurementUnit(['name' => 'Caja', 'abbreviation' => 'cja']));

    $service = new MeasurementUnitUpdateService($repository);

    expect($service->execute('unit-uuid', $command)->name)->toBe('Caja');
});

test('it throws when updating a missing measurement unit', function () {
    $command = new UpdateMeasurementUnitCommand(name: 'Caja', abbreviation: 'cja');

    $repository = Mockery::mock(MeasurementUnitRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new MeasurementUnitUpdateService($repository);

    $service->execute('missing-uuid', $command);
})->throws(MeasurementUnitNotFoundException::class);
