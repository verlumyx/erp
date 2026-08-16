<?php

declare(strict_types=1);

use App\Modules\ClientType\Exceptions\ClientTypeNotFoundException;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;
use App\Modules\ClientType\Services\ClientTypeFindService;

uses(Tests\TestCase::class);

test('it returns the client type when it exists', function () {
    $repository = Mockery::mock(ClientTypeRepositoryInterface::class);
    $repository->expects('findById')
        ->with('client-type-uuid', null)
        ->andReturn(new ClientType(['name' => 'Corporativo']));

    $service = new ClientTypeFindService($repository);

    expect($service->execute('client-type-uuid')->name)->toBe('Corporativo');
});

test('it throws when the client type does not exist', function () {
    $repository = Mockery::mock(ClientTypeRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new ClientTypeFindService($repository);

    $service->execute('missing-uuid');
})->throws(ClientTypeNotFoundException::class);
