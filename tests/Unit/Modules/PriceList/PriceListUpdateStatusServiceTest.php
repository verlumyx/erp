<?php

declare(strict_types=1);

use App\Modules\PriceList\Commands\UpdateStatusPriceListCommand;
use App\Modules\PriceList\Exceptions\PriceListNotFoundException;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\PriceList\Services\PriceListUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the price list status', function () {
    $command = new UpdateStatusPriceListCommand(status: 'inactive');
    $model = new PriceList(['id' => 'price-list-uuid', 'status' => 'active']);

    $repository = Mockery::mock(PriceListRepositoryInterface::class);
    $repository->expects('findById')->with('price-list-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('price-list-uuid', 'company-uuid')
        ->andReturn(new PriceList(['id' => 'price-list-uuid', 'status' => 'inactive']));

    $service = new PriceListUpdateStatusService($repository);

    expect($service->execute('price-list-uuid', $command, 'company-uuid')->status)->toBe('inactive');
});

test('it throws when changing the status of a missing price list', function () {
    $repository = Mockery::mock(PriceListRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new PriceListUpdateStatusService($repository);

    $service->execute('missing-uuid', new UpdateStatusPriceListCommand(status: 'inactive'));
})->throws(PriceListNotFoundException::class);
