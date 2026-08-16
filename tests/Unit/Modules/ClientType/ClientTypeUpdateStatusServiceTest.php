<?php

declare(strict_types=1);

use App\Modules\ClientType\Commands\UpdateStatusClientTypeCommand;
use App\Modules\ClientType\Exceptions\ClientTypeNotFoundException;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;
use App\Modules\ClientType\Services\ClientTypeUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the status and returns the refreshed model', function () {
    $command = new UpdateStatusClientTypeCommand(status: 'inactive');
    $model = new ClientType(['status' => 'active']);

    $repository = Mockery::mock(ClientTypeRepositoryInterface::class);
    $repository->expects('findById')->with('client-type-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('client-type-uuid', null)
        ->andReturn(new ClientType(['status' => 'inactive']));

    $service = new ClientTypeUpdateStatusService($repository);

    expect($service->execute('client-type-uuid', $command)->status)->toBe('inactive');
});

test('it throws when changing the status of a missing client type', function () {
    $command = new UpdateStatusClientTypeCommand(status: 'inactive');

    $repository = Mockery::mock(ClientTypeRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new ClientTypeUpdateStatusService($repository);

    $service->execute('missing-uuid', $command);
})->throws(ClientTypeNotFoundException::class);
