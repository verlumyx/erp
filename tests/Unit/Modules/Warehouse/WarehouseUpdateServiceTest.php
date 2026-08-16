<?php

declare(strict_types=1);

use App\Modules\Warehouse\Commands\UpdateWarehouseCommand;
use App\Modules\Warehouse\Exceptions\WarehouseNotFoundException;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\Warehouse\Services\WarehouseUpdateService;

uses(Tests\TestCase::class);

test('it updates the warehouse and returns the refreshed model', function () {
    $command = new UpdateWarehouseCommand(name: 'Nombre nuevo', type: 'branch');
    $model = new Warehouse(['id' => 'warehouse-uuid', 'name' => 'Nombre viejo']);

    $repository = Mockery::mock(WarehouseRepositoryInterface::class);
    $repository->expects('findById')->with('warehouse-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('warehouse-uuid', 'company-uuid')
        ->andReturn(new Warehouse(['id' => 'warehouse-uuid', 'name' => 'Nombre nuevo']));

    $result = (new WarehouseUpdateService($repository))->execute('warehouse-uuid', $command, 'company-uuid');

    expect($result->name)->toBe('Nombre nuevo');
});

test('it throws when updating a missing warehouse', function () {
    $repository = Mockery::mock(WarehouseRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new WarehouseUpdateService($repository))->execute('missing-uuid', new UpdateWarehouseCommand(name: 'X'));
})->throws(WarehouseNotFoundException::class);
