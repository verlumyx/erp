<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Commands\UpdatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\PurchaseOrder\Services\PurchaseOrderUpdateService;

uses(Tests\TestCase::class);

function updatePurchaseOrderCommand(): UpdatePurchaseOrderCommand
{
    return new UpdatePurchaseOrderCommand(
        supplierId: 'supplier-uuid',
        warehouseId: 'warehouse-uuid',
        orderDate: '2026-08-16',
    );
}

test('it updates the order and returns the fresh model', function () {
    $command = updatePurchaseOrderCommand();
    $model = new PurchaseOrder(['id' => 'order-uuid', 'supplier_reference' => 'COT-1']);

    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('findById')->with('order-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('order-uuid', 'company-uuid')
        ->andReturn(new PurchaseOrder(['id' => 'order-uuid', 'supplier_reference' => 'COT-2']));

    $service = new PurchaseOrderUpdateService($repository);

    expect($service->execute('order-uuid', $command, 'company-uuid')->supplier_reference)->toBe('COT-2');
});

test('it throws when the order does not exist', function () {
    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new PurchaseOrderUpdateService($repository);

    $service->execute('missing-uuid', updatePurchaseOrderCommand());
})->throws(PurchaseOrderNotFoundException::class);
