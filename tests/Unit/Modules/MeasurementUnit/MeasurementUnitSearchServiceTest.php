<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Commands\SearchMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use App\Modules\MeasurementUnit\Services\MeasurementUnitSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchMeasurementUnitCommand(filters: ['name' => 'kilo'], limit: 10, offset: 0);
    $expected = ['data' => [new MeasurementUnit(['name' => 'Kilogramo'])], 'total' => 1];

    $repository = Mockery::mock(MeasurementUnitRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new MeasurementUnitSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
