<?php

declare(strict_types=1);

use App\Modules\PriceList\Commands\UpdatePriceListCommand;
use App\Modules\PriceList\Exceptions\PriceListNotFoundException;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\PriceList\Services\PriceListUpdateService;

uses(Tests\TestCase::class);

test('it updates a price list and returns the refreshed model', function () {
    $command = new UpdatePriceListCommand(name: 'Mayorista 2026', description: 'Actualizada');
    $model = new PriceList(['id' => 'price-list-uuid', 'name' => 'Mayorista']);

    $repository = Mockery::mock(PriceListRepositoryInterface::class);
    $repository->expects('findById')->with('price-list-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('price-list-uuid', 'company-uuid')
        ->andReturn(new PriceList(['id' => 'price-list-uuid', 'name' => 'Mayorista 2026']));

    $service = new PriceListUpdateService($repository);

    expect($service->execute('price-list-uuid', $command, 'company-uuid')->name)->toBe('Mayorista 2026');
});

test('it throws when updating a missing price list', function () {
    $repository = Mockery::mock(PriceListRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new PriceListUpdateService($repository);

    $service->execute('missing-uuid', new UpdatePriceListCommand(name: 'X'));
})->throws(PriceListNotFoundException::class);
