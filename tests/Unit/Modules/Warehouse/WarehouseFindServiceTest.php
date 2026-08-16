<?php

declare(strict_types=1);

use App\Modules\Warehouse\Exceptions\WarehouseNotFoundException;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\Warehouse\Services\WarehouseFindService;

uses(Tests\TestCase::class);

test('it returns the warehouse when it exists', function () {
    $repository = Mockery::mock(WarehouseRepositoryInterface::class);
    $repository->expects('findById')
        ->with('warehouse-uuid', 'company-uuid')
        ->andReturn(new Warehouse(['id' => 'warehouse-uuid', 'name' => 'Bodega Central']));

    $service = new WarehouseFindService($repository);

    expect($service->execute('warehouse-uuid', 'company-uuid')->name)->toBe('Bodega Central');
});

test('it throws when the warehouse does not exist', function () {
    $repository = Mockery::mock(WarehouseRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new WarehouseFindService($repository))->execute('missing-uuid');
})->throws(WarehouseNotFoundException::class);
