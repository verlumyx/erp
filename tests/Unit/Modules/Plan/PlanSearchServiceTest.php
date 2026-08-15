<?php

declare(strict_types=1);

use App\Modules\Plan\Commands\SearchPlanCommand;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;
use App\Modules\Plan\Services\PlanSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchPlanCommand(filters: ['name' => 'netflix'], limit: 10, offset: 0);
    $expected = ['data' => [new Plan(['name' => 'Netflix Mensual'])], 'total' => 1];

    $repository = Mockery::mock(PlanRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new PlanSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
