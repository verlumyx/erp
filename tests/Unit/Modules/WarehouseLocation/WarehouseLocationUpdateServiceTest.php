<?php

declare(strict_types=1);

use App\Modules\WarehouseLocation\Commands\UpdateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Exceptions\WarehouseLocationNotFoundException;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use App\Modules\WarehouseLocation\Services\WarehouseLocationUpdateService;

uses(Tests\TestCase::class);

test('it updates the location and returns the refreshed model', function () {
    $command = new UpdateWarehouseLocationCommand(name: 'Nombre nuevo', locationCode: 'B-02-02');
    $model = new WarehouseLocation(['id' => 'location-uuid', 'name' => 'Nombre viejo']);

    $repository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $repository->expects('findById')->with('location-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('location-uuid', 'company-uuid')
        ->andReturn(new WarehouseLocation(['id' => 'location-uuid', 'name' => 'Nombre nuevo']));

    $result = (new WarehouseLocationUpdateService($repository))->execute('location-uuid', $command, 'company-uuid');

    expect($result->name)->toBe('Nombre nuevo');
});

test('it throws when updating a missing location', function () {
    $repository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new WarehouseLocationUpdateService($repository))
        ->execute('missing-uuid', new UpdateWarehouseLocationCommand(name: 'X', locationCode: 'X-01'));
})->throws(WarehouseLocationNotFoundException::class);
