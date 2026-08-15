<?php

declare(strict_types=1);

use App\Modules\Service\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use App\Modules\Service\Services\ServiceFindService;

uses(Tests\TestCase::class);

test('it returns the service when found', function () {
    $model = new Service(['id' => 'service-uuid', 'name' => 'Netflix']);

    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->expects('findById')->with('service-uuid', null)->andReturn($model);

    $service = new ServiceFindService($repository);

    expect($service->execute('service-uuid'))->toBe($model);
});

test('it throws when the service is missing', function () {
    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new ServiceFindService($repository);

    $service->execute('missing');
})->throws(ServiceNotFoundException::class);
