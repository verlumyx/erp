<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
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

test('it updates the order refreshing its rates', function () {
    $command = updatePurchaseOrderCommand();
    $model = new PurchaseOrder(['id' => 'order-uuid', 'supplier_reference' => 'COT-1']);
    $rates = new DocumentRatesData('USD', 36.5, 'USD', 36.5);

    $resolver = Mockery::mock(DocumentRatesResolverInterface::class);
    $resolver->expects('forDocument')
        ->with('company-uuid', 'USD', '2026-08-16', null)
        ->andReturn($rates);

    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('findById')->with('order-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command, $rates);
    $repository->expects('findOrFail')
        ->with('order-uuid', 'company-uuid')
        ->andReturn(new PurchaseOrder(['id' => 'order-uuid', 'supplier_reference' => 'COT-2']));

    $service = new PurchaseOrderUpdateService($repository, $resolver);

    expect($service->execute('order-uuid', $command, 'company-uuid')->supplier_reference)->toBe('COT-2');
});

test('it throws when the order does not exist', function () {
    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new PurchaseOrderUpdateService(
        $repository,
        Mockery::mock(DocumentRatesResolverInterface::class),
    );

    $service->execute('missing-uuid', updatePurchaseOrderCommand());
})->throws(PurchaseOrderNotFoundException::class);
