<?php

declare(strict_types=1);

use App\Modules\Client\Commands\UpdateClientCommand;
use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use App\Modules\Client\Services\ClientUpdateService;

uses(Tests\TestCase::class);

test('it updates an existing client', function () {
    $command = new UpdateClientCommand(name: 'Updated');
    $model = new Client(['id' => 'client-uuid', 'name' => 'Old']);

    $repository = Mockery::mock(ClientRepositoryInterface::class);
    $repository->expects('findById')->with('client-uuid', null)->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('client-uuid', null)
        ->andReturn(new Client(['id' => 'client-uuid', 'name' => 'Updated']));

    $service = new ClientUpdateService($repository);
    $result = $service->execute('client-uuid', $command);

    expect($result->name)->toBe('Updated');
});

test('it throws when updating a missing client', function () {
    $repository = Mockery::mock(ClientRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ClientUpdateService($repository);

    $service->execute('missing', new UpdateClientCommand(name: 'X'));
})->throws(ClientNotFoundException::class);
