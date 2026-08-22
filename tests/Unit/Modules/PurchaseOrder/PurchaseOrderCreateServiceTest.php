<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseOrder\Commands\CreatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\PurchaseOrderLineData;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\PurchaseOrder\Services\PurchaseOrderCreateService;

uses(Tests\TestCase::class);

test('it creates a purchase order with the rates resolved for its date', function () {
    $command = new CreatePurchaseOrderCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        supplierId: 'supplier-uuid',
        warehouseId: 'warehouse-uuid',
        orderDate: '2026-08-16',
        createdBy: 'user-uuid',
        lines: PurchaseOrderLineData::collection([
            ['item_id' => 'item-uuid', 'measurement_unit_id' => 'unit-uuid', 'quantity' => 3, 'unit_price' => 10],
        ]),
        currency: 'EUR',
    );

    $rates = new DocumentRatesData('EUR', 40.0, 'USD', 36.5);

    $resolver = Mockery::mock(DocumentRatesResolverInterface::class);
    $resolver->expects('forDocument')
        ->with('company-uuid', 'EUR', '2026-08-16', null)
        ->andReturn($rates);

    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('create')->with($command, $rates);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new PurchaseOrder(['id' => $command->id, 'code' => 'OCO000001']));

    $service = new PurchaseOrderCreateService($repository, $resolver);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(PurchaseOrder::class);
    expect($result->code)->toBe('OCO000001');
});
