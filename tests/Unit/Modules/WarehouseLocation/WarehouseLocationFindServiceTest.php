<?php

declare(strict_types=1);

use App\Modules\WarehouseLocation\Exceptions\WarehouseLocationNotFoundException;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use App\Modules\WarehouseLocation\Services\WarehouseLocationFindService;

uses(Tests\TestCase::class);

test('it returns the location when it exists', function () {
    $repository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $repository->expects('findById')
        ->with('location-uuid', 'company-uuid')
        ->andReturn(new WarehouseLocation(['id' => 'location-uuid', 'name' => 'Estante A1']));

    $service = new WarehouseLocationFindService($repository);

    expect($service->execute('location-uuid', 'company-uuid')->name)->toBe('Estante A1');
});

test('it throws when the location does not exist', function () {
    $repository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    (new WarehouseLocationFindService($repository))->execute('missing-uuid');
})->throws(WarehouseLocationNotFoundException::class);
