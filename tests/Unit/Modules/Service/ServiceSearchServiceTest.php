<?php

declare(strict_types=1);

use App\Modules\Service\Commands\SearchServiceCommand;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use App\Modules\Service\Services\ServiceSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchServiceCommand(filters: ['name' => 'net'], limit: 10, offset: 0);
    $expected = ['data' => [new Service(['name' => 'Netflix'])], 'total' => 1];

    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new ServiceSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
