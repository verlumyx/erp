<?php

declare(strict_types=1);

use App\Modules\Supplier\Commands\UpdateStatusSupplierCommand;
use App\Modules\Supplier\Exceptions\SupplierNotFoundException;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Supplier\Services\SupplierUpdateStatusService;

uses(Tests\TestCase::class);

test('it changes the status and returns the fresh model', function () {
    $command = new UpdateStatusSupplierCommand(status: 'inactive');
    $model = new Supplier(['id' => 'supplier-uuid', 'status' => 'active']);

    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('findById')->with('supplier-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('supplier-uuid', 'company-uuid')
        ->andReturn(new Supplier(['id' => 'supplier-uuid', 'status' => 'inactive']));

    $service = new SupplierUpdateStatusService($repository);

    expect($service->execute('supplier-uuid', $command, 'company-uuid')->status)->toBe('inactive');
});

test('it throws when the supplier does not exist', function () {
    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new SupplierUpdateStatusService($repository);

    $service->execute('missing-uuid', new UpdateStatusSupplierCommand(status: 'inactive'));
})->throws(SupplierNotFoundException::class);
