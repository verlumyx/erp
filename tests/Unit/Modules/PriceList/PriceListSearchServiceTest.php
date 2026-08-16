<?php

declare(strict_types=1);

use App\Modules\PriceList\Commands\SearchPriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\PriceList\Services\PriceListSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchPriceListCommand(filters: ['name' => 'mayorista'], limit: 10, offset: 0);
    $expected = ['data' => [new PriceList(['name' => 'Mayorista'])], 'total' => 1];

    $repository = Mockery::mock(PriceListRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new PriceListSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
