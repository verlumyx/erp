<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\SalesOrder\Services\SalesOrderCreateService;

uses(Tests\TestCase::class);

test('it creates a sales order and returns the persisted model', function () {
    $command = new CreateSalesOrderCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        clientId: 'client-uuid',
        warehouseId: 'warehouse-uuid',
        orderDate: '2026-08-16',
        createdBy: 'user-uuid',
    );

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new SalesOrder(['id' => $command->id, 'code' => 'OVE000001']));

    $service = new SalesOrderCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(SalesOrder::class);
    expect($result->code)->toBe('OVE000001');
});
