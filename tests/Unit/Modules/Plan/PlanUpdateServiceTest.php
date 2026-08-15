<?php

declare(strict_types=1);

use App\Modules\Plan\Commands\UpdatePlanCommand;
use App\Modules\Plan\Exceptions\PlanNotFoundException;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;
use App\Modules\Plan\Services\PlanUpdateService;

uses(Tests\TestCase::class);

test('it updates a plan and returns the refreshed model', function () {
    $command = new UpdatePlanCommand(
        serviceId: 'service-uuid',
        name: 'Netflix Trimestral',
        capacity: 'full_account',
        durationDays: 90,
        salePrice: 30.0,
        roiTargetPct: 50.0,
    );
    $model = new Plan(['id' => 'plan-uuid', 'name' => 'Netflix Mensual']);

    $repository = Mockery::mock(PlanRepositoryInterface::class);
    $repository->expects('findById')->with('plan-uuid', null)->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('plan-uuid', null)
        ->andReturn(new Plan(['id' => 'plan-uuid', 'name' => 'Netflix Trimestral']));

    $service = new PlanUpdateService($repository);
    $result = $service->execute('plan-uuid', $command);

    expect($result->name)->toBe('Netflix Trimestral');
});

test('it throws when updating a missing plan', function () {
    $repository = Mockery::mock(PlanRepositoryInterface::class);
    $repository->expects('findById')->with('missing', null)->andReturnNull();

    $service = new PlanUpdateService($repository);

    $service->execute('missing', new UpdatePlanCommand(
        serviceId: 'service-uuid',
        name: 'X',
        capacity: 'profile',
        durationDays: 30,
        salePrice: 1.0,
        roiTargetPct: 1.0,
    ));
})->throws(PlanNotFoundException::class);
