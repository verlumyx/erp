<?php

declare(strict_types=1);

use App\Modules\Client\Commands\UpdateStatusClientCommand;
use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use App\Modules\Client\Services\ClientUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the client status', function () {
    $command = new UpdateStatusClientCommand(status: 'inactive');
    $model = new Client(['id' => 'client-uuid', 'status' => 'active']);

    $repository = Mockery::mock(ClientRepositoryInterface::class);
    $repository->expects('findById')->with('client-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('client-uuid', null)
        ->andReturn(new Client(['id' => 'client-uuid', 'status' => 'inactive']));

    $service = new ClientUpdateStatusService($repository);
    $result = $service->execute('client-uuid', $command);

    expect($result->status)->toBe('inactive');
});

test('it throws when changing status of a missing client', function () {
    $repository = Mockery::mock(ClientRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ClientUpdateStatusService($repository);

    $service->execute('missing', new UpdateStatusClientCommand(status: 'inactive'));
})->throws(ClientNotFoundException::class);
