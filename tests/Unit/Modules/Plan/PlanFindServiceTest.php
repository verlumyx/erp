<?php

declare(strict_types=1);

use App\Modules\Plan\Exceptions\PlanNotFoundException;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;
use App\Modules\Plan\Services\PlanFindService;

uses(Tests\TestCase::class);

test('it returns the plan when found', function () {
    $model = new Plan(['id' => 'plan-uuid', 'name' => 'Netflix Mensual']);

    $repository = Mockery::mock(PlanRepositoryInterface::class);
    $repository->expects('findById')->with('plan-uuid', null)->andReturn($model);

    $service = new PlanFindService($repository);

    expect($service->execute('plan-uuid'))->toBe($model);
});

test('it throws when the plan is missing', function () {
    $repository = Mockery::mock(PlanRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new PlanFindService($repository);

    $service->execute('missing');
})->throws(PlanNotFoundException::class);
