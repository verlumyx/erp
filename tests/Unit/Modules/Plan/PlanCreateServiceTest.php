<?php

declare(strict_types=1);

use App\Modules\Plan\Commands\CreatePlanCommand;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;
use App\Modules\Plan\Services\PlanCreateService;

uses(Tests\TestCase::class);

test('it creates a plan and returns the persisted model', function () {
    $command = new CreatePlanCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        serviceId: 'service-uuid',
        name: 'Netflix Mensual',
        capacity: 'profile',
        durationDays: 30,
        salePrice: 12.5,
        roiTargetPct: 40.0,
    );

    $repository = Mockery::mock(PlanRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new Plan(['id' => $command->id, 'name' => 'Netflix Mensual']));

    $service = new PlanCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Plan::class);
    expect($result->name)->toBe('Netflix Mensual');
});
