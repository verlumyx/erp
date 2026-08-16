<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Commands\UpdateStatusSalesOrderCommand;
use App\Modules\SalesOrder\Exceptions\SalesOrderNotFoundException;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\SalesOrder\Services\SalesOrderUpdateStatusService;

uses(Tests\TestCase::class);

test('it changes the status and returns the sales order reloaded', function () {
    $command = new UpdateStatusSalesOrderCommand(status: 'confirmed', approvedBy: 'user-uuid');

    $existing = new SalesOrder(['id' => 'order-uuid', 'status' => 'draft']);
    $reloaded = new SalesOrder(['id' => 'order-uuid', 'status' => 'confirmed']);

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('findById')->with('order-uuid', 'company-uuid')->andReturn($existing);
    $repository->expects('updateStatus')->with($existing, $command);
    $repository->expects('findOrFail')->with('order-uuid', 'company-uuid')->andReturn($reloaded);

    $service = new SalesOrderUpdateStatusService($repository);

    expect($service->execute('order-uuid', $command, 'company-uuid')->status)->toBe('confirmed');
});

test('it throws when the sales order is missing', function () {
    $command = new UpdateStatusSalesOrderCommand(status: 'cancelled');

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new SalesOrderUpdateStatusService($repository);

    $service->execute('missing', $command);
})->throws(SalesOrderNotFoundException::class);
