<?php

declare(strict_types=1);

use App\Modules\Service\Commands\UpdateServiceCommand;
use App\Modules\Service\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use App\Modules\Service\Services\ServiceUpdateService;

uses(Tests\TestCase::class);

test('it updates a service and returns the fresh model', function () {
    $command = new UpdateServiceCommand(name: 'Disney+', maxProfiles: 4);
    $model = new Service(['id' => 'service-uuid', 'name' => 'Disney']);

    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->expects('findById')->with('service-uuid', null)->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('service-uuid', null)
        ->andReturn(new Service(['id' => 'service-uuid', 'name' => 'Disney+']));

    $service = new ServiceUpdateService($repository);
    $result = $service->execute('service-uuid', $command);

    expect($result->name)->toBe('Disney+');
});

test('it throws when updating a missing service', function () {
    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ServiceUpdateService($repository);

    $service->execute('missing', new UpdateServiceCommand(name: 'X', maxProfiles: 1));
})->throws(ServiceNotFoundException::class);
