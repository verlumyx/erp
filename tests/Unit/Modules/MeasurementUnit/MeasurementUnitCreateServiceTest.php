<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Commands\CreateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use App\Modules\MeasurementUnit\Services\MeasurementUnitCreateService;

uses(Tests\TestCase::class);

test('it creates a measurement unit and returns the persisted model', function () {
    $command = new CreateMeasurementUnitCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Kilogramo',
        abbreviation: 'kg',
        createdBy: 'user-uuid',
        description: 'Unidad de masa',
    );

    $repository = Mockery::mock(MeasurementUnitRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new MeasurementUnit(['id' => $command->id, 'name' => 'Kilogramo', 'abbreviation' => 'kg']));

    $service = new MeasurementUnitCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(MeasurementUnit::class);
    expect($result->name)->toBe('Kilogramo');
    expect($result->abbreviation)->toBe('kg');
});
