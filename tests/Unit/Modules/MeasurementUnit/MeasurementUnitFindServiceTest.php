<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Exceptions\MeasurementUnitNotFoundException;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use App\Modules\MeasurementUnit\Services\MeasurementUnitFindService;

uses(Tests\TestCase::class);

test('it returns the measurement unit when it exists', function () {
    $repository = Mockery::mock(MeasurementUnitRepositoryInterface::class);
    $repository->expects('findById')
        ->with('unit-uuid', null)
        ->andReturn(new MeasurementUnit(['name' => 'Litro']));

    $service = new MeasurementUnitFindService($repository);

    expect($service->execute('unit-uuid')->name)->toBe('Litro');
});

test('it throws when the measurement unit does not exist', function () {
    $repository = Mockery::mock(MeasurementUnitRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new MeasurementUnitFindService($repository);

    $service->execute('missing-uuid');
})->throws(MeasurementUnitNotFoundException::class);
