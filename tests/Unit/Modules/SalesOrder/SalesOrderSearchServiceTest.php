<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Commands\SearchSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Modules\SalesOrder\Services\SalesOrderSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchSalesOrderCommand(
        filters: ['status' => 'confirmed'],
        limit: 10,
        offset: 0,
        companyId: 'company-uuid',
    );

    $expected = ['data' => [new SalesOrder(['code' => 'OVE000001'])], 'total' => 1];

    $repository = Mockery::mock(SalesOrderRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new SalesOrderSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
