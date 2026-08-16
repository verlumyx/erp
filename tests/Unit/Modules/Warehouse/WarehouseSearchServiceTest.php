<?php

declare(strict_types=1);

use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\Warehouse\Services\WarehouseSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchWarehouseCommand(
        filters: ['name' => 'Central'],
        limit: 10,
        offset: 0,
        companyId: 'company-uuid',
    );

    $expected = ['data' => [new Warehouse(['name' => 'Bodega Central'])], 'total' => 1];

    $repository = Mockery::mock(WarehouseRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $result = (new WarehouseSearchService($repository))->execute($command);

    expect($result['total'])->toBe(1);
    expect($result['data'][0]->name)->toBe('Bodega Central');
});
