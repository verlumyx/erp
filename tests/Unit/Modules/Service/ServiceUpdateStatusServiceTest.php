<?php

declare(strict_types=1);

use App\Modules\Service\Commands\UpdateStatusServiceCommand;
use App\Modules\Service\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use App\Modules\Service\Services\ServiceUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the service status', function () {
    $command = new UpdateStatusServiceCommand(active: false);
    $model = new Service(['id' => 'service-uuid', 'active' => true]);

    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->expects('findById')->with('service-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('service-uuid', null)
        ->andReturn(new Service(['id' => 'service-uuid', 'active' => false]));

    $service = new ServiceUpdateStatusService($repository);
    $result = $service->execute('service-uuid', $command);

    expect($result->active)->toBeFalse();
});

test('it throws when changing status of a missing service', function () {
    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ServiceUpdateStatusService($repository);

    $service->execute('missing', new UpdateStatusServiceCommand(active: false));
})->throws(ServiceNotFoundException::class);
