<?php

declare(strict_types=1);

use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use App\Modules\Client\Services\ClientSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchClientCommand(filters: ['search' => 'acme'], limit: 10, offset: 0);
    $expected = ['data' => [new Client(['name' => 'Acme'])], 'total' => 1];

    $repository = Mockery::mock(ClientRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new ClientSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
