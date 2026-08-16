<?php

declare(strict_types=1);

use App\Modules\WarehouseLocation\Commands\UpdateStatusWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Exceptions\WarehouseLocationNotFoundException;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use App\Modules\WarehouseLocation\Services\WarehouseLocationUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the location status', function () {
    $command = new UpdateStatusWarehouseLocationCommand(status: 'inactive');
    $model = new WarehouseLocation(['id' => 'location-uuid', 'status' => 'active']);

    $repository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $repository->expects('findById')->with('location-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('location-uuid', null)
        ->andReturn(new WarehouseLocation(['id' => 'location-uuid', 'status' => 'inactive']));

    $result = (new WarehouseLocationUpdateStatusService($repository))->execute('location-uuid', $command);

    expect($result->status)->toBe('inactive');
});

test('it throws when changing the status of a missing location', function () {
    $repository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new WarehouseLocationUpdateStatusService($repository))
        ->execute('missing-uuid', new UpdateStatusWarehouseLocationCommand(status: 'inactive'));
})->throws(WarehouseLocationNotFoundException::class);
