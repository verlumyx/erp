<?php

declare(strict_types=1);

use App\Modules\SupplierType\Commands\UpdateSupplierTypeCommand;
use App\Modules\SupplierType\Exceptions\SupplierTypeNotFoundException;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;
use App\Modules\SupplierType\Services\SupplierTypeUpdateService;

uses(Tests\TestCase::class);

test('it updates the supplier type and returns the refreshed model', function () {
    $command = new UpdateSupplierTypeCommand(name: 'Servicios');
    $model = new SupplierType(['name' => 'Servicio']);

    $repository = Mockery::mock(SupplierTypeRepositoryInterface::class);
    $repository->expects('findById')->with('supplier-type-uuid', null)->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('supplier-type-uuid', null)
        ->andReturn(new SupplierType(['name' => 'Servicios']));

    $service = new SupplierTypeUpdateService($repository);

    expect($service->execute('supplier-type-uuid', $command)->name)->toBe('Servicios');
});

test('it throws when updating a missing supplier type', function () {
    $command = new UpdateSupplierTypeCommand(name: 'Servicios');

    $repository = Mockery::mock(SupplierTypeRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new SupplierTypeUpdateService($repository);

    $service->execute('missing-uuid', $command);
})->throws(SupplierTypeNotFoundException::class);
