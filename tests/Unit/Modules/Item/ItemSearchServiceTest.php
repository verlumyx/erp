<?php

declare(strict_types=1);

use App\Modules\Item\Commands\SearchItemCommand;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\Item\Services\ItemSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchItemCommand(filters: ['sku' => 'MART'], limit: 10, companyId: 'company-uuid');
    $expected = ['data' => [new Item(['name' => 'Martillo'])], 'total' => 1];

    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new ItemSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
