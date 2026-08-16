<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Exceptions\SalesOrderNotFoundException;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\SalesOrder\Services\SalesOrderFindService;

uses(Tests\TestCase::class);

test('it returns the sales order when found', function () {
    $order = new SalesOrder(['id' => 'order-uuid', 'code' => 'OVE000001']);

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('findById')->with('order-uuid', null)->andReturn($order);

    $service = new SalesOrderFindService($repository);

    expect($service->execute('order-uuid'))->toBe($order);
});

test('it scopes the lookup to the active company', function () {
    $order = new SalesOrder(['id' => 'order-uuid']);

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('findById')->with('order-uuid', 'company-uuid')->andReturn($order);

    $service = new SalesOrderFindService($repository);

    expect($service->execute('order-uuid', 'company-uuid'))->toBe($order);
});

test('it throws when the sales order is missing', function () {
    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new SalesOrderFindService($repository);

    $service->execute('missing');
})->throws(SalesOrderNotFoundException::class);
