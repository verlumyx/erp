<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Commands\UpdateSalesOrderCommand;
use App\Modules\SalesOrder\Exceptions\SalesOrderNotFoundException;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\SalesOrder\Services\SalesOrderUpdateService;

uses(Tests\TestCase::class);

test('it updates the sales order and returns it reloaded', function () {
    $command = new UpdateSalesOrderCommand(
        clientId: 'client-uuid',
        warehouseId: 'warehouse-uuid',
        orderDate: '2026-08-16',
    );

    $existing = new SalesOrder(['id' => 'order-uuid']);
    $reloaded = new SalesOrder(['id' => 'order-uuid', 'client_reference' => 'OC-1']);

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('findById')->with('order-uuid', 'company-uuid')->andReturn($existing);
    $repository->expects('update')->with($existing, $command);
    $repository->expects('findOrFail')->with('order-uuid', 'company-uuid')->andReturn($reloaded);

    $service = new SalesOrderUpdateService($repository);

    expect($service->execute('order-uuid', $command, 'company-uuid'))->toBe($reloaded);
});

test('it throws when the sales order is missing', function () {
    $command = new UpdateSalesOrderCommand(
        clientId: 'client-uuid',
        warehouseId: 'warehouse-uuid',
        orderDate: '2026-08-16',
    );

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new SalesOrderUpdateService($repository);

    $service->execute('missing', $command);
})->throws(SalesOrderNotFoundException::class);
