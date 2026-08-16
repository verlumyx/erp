<?php

declare(strict_types=1);

use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use App\Modules\Tax\Services\TaxSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchTaxCommand(filters: ['name' => 'iva'], limit: 10, offset: 0);
    $expected = ['data' => [new Tax(['name' => 'IVA 15%'])], 'total' => 1];

    $repository = Mockery::mock(TaxRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new TaxSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
