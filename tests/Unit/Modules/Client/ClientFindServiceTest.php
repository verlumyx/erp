<?php

declare(strict_types=1);

use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use App\Modules\Client\Services\ClientFindService;

uses(Tests\TestCase::class);

test('it returns the client when found', function () {
    $client = new Client(['id' => 'client-uuid', 'name' => 'Acme']);

    $repository = Mockery::mock(ClientRepositoryInterface::class);
    $repository->expects('findById')->with('client-uuid', null)->andReturn($client);

    $service = new ClientFindService($repository);

    expect($service->execute('client-uuid'))->toBe($client);
});

test('it throws when the client is missing', function () {
    $repository = Mockery::mock(ClientRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ClientFindService($repository);

    $service->execute('missing');
})->throws(ClientNotFoundException::class);
