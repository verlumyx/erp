<?php

declare(strict_types=1);

use App\Modules\SupplierType\Exceptions\SupplierTypeNotFoundException;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;
use App\Modules\SupplierType\Services\SupplierTypeFindService;

uses(Tests\TestCase::class);

test('it returns the supplier type when it exists', function () {
    $repository = Mockery::mock(SupplierTypeRepositoryInterface::class);
    $repository->expects('findById')
        ->with('supplier-type-uuid', null)
        ->andReturn(new SupplierType(['name' => 'Importador']));

    $service = new SupplierTypeFindService($repository);

    expect($service->execute('supplier-type-uuid')->name)->toBe('Importador');
});

test('it throws when the supplier type does not exist', function () {
    $repository = Mockery::mock(SupplierTypeRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new SupplierTypeFindService($repository);

    $service->execute('missing-uuid');
})->throws(SupplierTypeNotFoundException::class);
