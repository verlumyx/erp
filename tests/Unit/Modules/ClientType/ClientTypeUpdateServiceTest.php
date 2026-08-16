<?php

declare(strict_types=1);

use App\Modules\ClientType\Commands\UpdateClientTypeCommand;
use App\Modules\ClientType\Exceptions\ClientTypeNotFoundException;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;
use App\Modules\ClientType\Services\ClientTypeUpdateService;

uses(Tests\TestCase::class);

test('it updates the client type and returns the refreshed model', function () {
    $command = new UpdateClientTypeCommand(name: 'Detalle');
    $model = new ClientType(['name' => 'Detal']);

    $repository = Mockery::mock(ClientTypeRepositoryInterface::class);
    $repository->expects('findById')->with('client-type-uuid', null)->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('client-type-uuid', null)
        ->andReturn(new ClientType(['name' => 'Detalle']));

    $service = new ClientTypeUpdateService($repository);

    expect($service->execute('client-type-uuid', $command)->name)->toBe('Detalle');
});

test('it throws when updating a missing client type', function () {
    $command = new UpdateClientTypeCommand(name: 'Detalle');

    $repository = Mockery::mock(ClientTypeRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new ClientTypeUpdateService($repository);

    $service->execute('missing-uuid', $command);
})->throws(ClientTypeNotFoundException::class);
