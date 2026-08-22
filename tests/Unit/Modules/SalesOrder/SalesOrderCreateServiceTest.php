<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\SalesOrder\Services\SalesOrderCreateService;

uses(Tests\TestCase::class);

test('it creates a sales order with the rates resolved for its date', function () {
    $command = new CreateSalesOrderCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        clientId: 'client-uuid',
        warehouseId: 'warehouse-uuid',
        orderDate: '2026-08-16',
        createdBy: 'user-uuid',
        currency: 'EUR',
    );

    $rates = new DocumentRatesData('EUR', 40.0, 'USD', 36.5);

    $resolver = Mockery::mock(DocumentRatesResolverInterface::class);
    $resolver->expects('forDocument')
        ->with('company-uuid', 'EUR', '2026-08-16', null)
        ->andReturn($rates);

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('create')->with($command, $rates);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new SalesOrder(['id' => $command->id, 'code' => 'OVE000001']));

    $service = new SalesOrderCreateService($repository, $resolver);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(SalesOrder::class);
    expect($result->code)->toBe('OVE000001');
});

test('it hands the manual rate of the payload to the resolver', function () {
    $command = new CreateSalesOrderCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        clientId: 'client-uuid',
        warehouseId: 'warehouse-uuid',
        orderDate: '2026-08-16',
        createdBy: 'user-uuid',
        exchangeRateOverride: '38.25',
    );

    $rates = new DocumentRatesData('USD', 38.25, 'USD', 38.25);

    $resolver = Mockery::mock(DocumentRatesResolverInterface::class);
    $resolver->expects('forDocument')
        ->with('company-uuid', 'USD', '2026-08-16', '38.25')
        ->andReturn($rates);

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('create')->with($command, $rates);
    $repository->expects('findOrFail')->andReturn(new SalesOrder(['id' => $command->id]));

    (new SalesOrderCreateService($repository, $resolver))->execute($command);
});
