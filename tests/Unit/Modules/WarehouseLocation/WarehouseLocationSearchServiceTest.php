<?php

declare(strict_types=1);

use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use App\Modules\WarehouseLocation\Services\WarehouseLocationSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchWarehouseLocationCommand(
        filters: ['warehouse_id' => 'warehouse-uuid'],
        limit: 10,
        offset: 0,
        companyId: 'company-uuid',
    );

    $expected = ['data' => [new WarehouseLocation(['name' => 'Estante A1'])], 'total' => 1];

    $repository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $result = (new WarehouseLocationSearchService($repository))->execute($command);

    expect($result['total'])->toBe(1);
    expect($result['data'][0]->name)->toBe('Estante A1');
});
