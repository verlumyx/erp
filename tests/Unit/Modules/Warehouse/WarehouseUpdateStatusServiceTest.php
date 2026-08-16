<?php

declare(strict_types=1);

use App\Modules\Warehouse\Commands\UpdateStatusWarehouseCommand;
use App\Modules\Warehouse\Exceptions\WarehouseNotFoundException;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\Warehouse\Services\WarehouseUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the warehouse status', function () {
    $command = new UpdateStatusWarehouseCommand(status: 'inactive');
    $model = new Warehouse(['id' => 'warehouse-uuid', 'status' => 'active']);

    $repository = Mockery::mock(WarehouseRepositoryInterface::class);
    $repository->expects('findById')->with('warehouse-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('warehouse-uuid', null)
        ->andReturn(new Warehouse(['id' => 'warehouse-uuid', 'status' => 'inactive']));

    $result = (new WarehouseUpdateStatusService($repository))->execute('warehouse-uuid', $command);

    expect($result->status)->toBe('inactive');
});

test('it throws when changing the status of a missing warehouse', function () {
    $repository = Mockery::mock(WarehouseRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new WarehouseUpdateStatusService($repository))
        ->execute('missing-uuid', new UpdateStatusWarehouseCommand(status: 'inactive'));
})->throws(WarehouseNotFoundException::class);
