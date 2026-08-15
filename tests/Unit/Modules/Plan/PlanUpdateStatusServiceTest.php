<?php

declare(strict_types=1);

use App\Modules\Plan\Commands\UpdateStatusPlanCommand;
use App\Modules\Plan\Exceptions\PlanNotFoundException;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;
use App\Modules\Plan\Services\PlanUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the plan active state', function () {
    $command = new UpdateStatusPlanCommand(active: false);
    $model = new Plan(['id' => 'plan-uuid', 'active' => true]);

    $repository = Mockery::mock(PlanRepositoryInterface::class);
    $repository->expects('findById')->with('plan-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('plan-uuid', null)
        ->andReturn(new Plan(['id' => 'plan-uuid', 'active' => false]));

    $service = new PlanUpdateStatusService($repository);
    $result = $service->execute('plan-uuid', $command);

    expect($result->active)->toBeFalse();
});

test('it throws when changing status of a missing plan', function () {
    $repository = Mockery::mock(PlanRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new PlanUpdateStatusService($repository);

    $service->execute('missing', new UpdateStatusPlanCommand(active: false));
})->throws(PlanNotFoundException::class);
