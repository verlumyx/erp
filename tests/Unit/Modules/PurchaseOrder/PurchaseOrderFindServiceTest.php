<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\PurchaseOrder\Services\PurchaseOrderFindService;

uses(Tests\TestCase::class);

test('it returns the order found by the repository', function () {
    $model = new PurchaseOrder(['id' => 'order-uuid', 'code' => 'OCO000001']);

    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('findById')->with('order-uuid', 'company-uuid')->andReturn($model);

    $service = new PurchaseOrderFindService($repository);

    expect($service->execute('order-uuid', 'company-uuid')->code)->toBe('OCO000001');
});

test('it throws when the order does not exist', function () {
    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new PurchaseOrderFindService($repository);

    $service->execute('missing-uuid');
})->throws(PurchaseOrderNotFoundException::class);
