<?php

declare(strict_types=1);

use App\Modules\Supplier\Exceptions\SupplierNotFoundException;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Supplier\Services\SupplierFindService;

uses(Tests\TestCase::class);

test('it returns the supplier when it exists', function () {
    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('findById')
        ->with('supplier-uuid', 'company-uuid')
        ->andReturn(new Supplier(['id' => 'supplier-uuid', 'name' => 'Andina']));

    $service = new SupplierFindService($repository);

    expect($service->execute('supplier-uuid', 'company-uuid')->name)->toBe('Andina');
});

test('it throws when the supplier does not exist', function () {
    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new SupplierFindService($repository);

    $service->execute('missing-uuid');
})->throws(SupplierNotFoundException::class);
