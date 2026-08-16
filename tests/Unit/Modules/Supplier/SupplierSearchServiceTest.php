<?php

declare(strict_types=1);

use App\Modules\Supplier\Commands\SearchSupplierCommand;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Supplier\Services\SupplierSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchSupplierCommand(
        filters: ['status' => 'active'],
        limit: 10,
        companyId: 'company-uuid',
    );

    $expected = ['data' => [new Supplier(['name' => 'Andina'])], 'total' => 1];

    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new SupplierSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
