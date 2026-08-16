<?php

declare(strict_types=1);

use App\Modules\Supplier\Commands\UpdateSupplierCommand;
use App\Modules\Supplier\Exceptions\SupplierNotFoundException;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Supplier\Services\SupplierUpdateService;

uses(Tests\TestCase::class);

test('it updates the supplier and returns the fresh model', function () {
    $command = new UpdateSupplierCommand(
        name: 'Nombre nuevo',
        documentType: 'J',
        documentNumber: '123456789',
    );

    $model = new Supplier(['id' => 'supplier-uuid', 'name' => 'Nombre viejo']);

    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('findById')->with('supplier-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('supplier-uuid', 'company-uuid')
        ->andReturn(new Supplier(['id' => 'supplier-uuid', 'name' => 'Nombre nuevo']));

    $service = new SupplierUpdateService($repository);

    expect($service->execute('supplier-uuid', $command, 'company-uuid')->name)->toBe('Nombre nuevo');
});

test('it throws when the supplier does not exist', function () {
    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new SupplierUpdateService($repository);

    $service->execute('missing-uuid', new UpdateSupplierCommand(
        name: 'Nombre',
        documentType: 'J',
        documentNumber: '1',
    ));
})->throws(SupplierNotFoundException::class);
