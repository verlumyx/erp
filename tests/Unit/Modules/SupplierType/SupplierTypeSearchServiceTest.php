<?php

declare(strict_types=1);

use App\Modules\SupplierType\Commands\SearchSupplierTypeCommand;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;
use App\Modules\SupplierType\Services\SupplierTypeSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchSupplierTypeCommand(filters: ['name' => 'nacio'], limit: 10, offset: 0);
    $expected = ['data' => [new SupplierType(['name' => 'Nacional'])], 'total' => 1];

    $repository = Mockery::mock(SupplierTypeRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new SupplierTypeSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
