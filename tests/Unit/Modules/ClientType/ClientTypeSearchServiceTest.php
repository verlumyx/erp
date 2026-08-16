<?php

declare(strict_types=1);

use App\Modules\ClientType\Commands\SearchClientTypeCommand;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;
use App\Modules\ClientType\Services\ClientTypeSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchClientTypeCommand(filters: ['name' => 'mayor'], limit: 10, offset: 0);
    $expected = ['data' => [new ClientType(['name' => 'Mayorista'])], 'total' => 1];

    $repository = Mockery::mock(ClientTypeRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new ClientTypeSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
