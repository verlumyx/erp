<?php

declare(strict_types=1);

use App\Modules\PriceList\Exceptions\PriceListNotFoundException;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\PriceList\Services\PriceListFindService;

uses(Tests\TestCase::class);

test('it returns the price list found by the repository', function () {
    $repository = Mockery::mock(PriceListRepositoryInterface::class);
    $repository->expects('findById')
        ->with('price-list-uuid', 'company-uuid')
        ->andReturn(new PriceList(['id' => 'price-list-uuid', 'name' => 'Mayorista']));

    $service = new PriceListFindService($repository);

    expect($service->execute('price-list-uuid', 'company-uuid')->name)->toBe('Mayorista');
});

test('it throws when the price list does not exist', function () {
    $repository = Mockery::mock(PriceListRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new PriceListFindService($repository);

    $service->execute('missing-uuid');
})->throws(PriceListNotFoundException::class);
