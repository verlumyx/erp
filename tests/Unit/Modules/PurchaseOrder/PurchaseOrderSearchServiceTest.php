<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Commands\SearchPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\PurchaseOrder\Services\PurchaseOrderSearchService;

uses(Tests\TestCase::class);

test('it forwards the command to the repository and returns its result', function () {
    $command = new SearchPurchaseOrderCommand(
        filters: ['status' => 'draft'],
        limit: 10,
        offset: 0,
        companyId: 'company-uuid',
    );

    $expected = [
        'data' => [new PurchaseOrder(['id' => 'order-uuid'])],
        'total' => 1,
    ];

    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new PurchaseOrderSearchService($repository);
    $result = $service->execute($command);

    expect($result['total'])->toBe(1);
    expect($result['data'])->toHaveCount(1);
});
