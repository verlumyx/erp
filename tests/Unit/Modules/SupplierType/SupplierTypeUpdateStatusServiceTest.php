<?php

declare(strict_types=1);

use App\Modules\SupplierType\Commands\UpdateStatusSupplierTypeCommand;
use App\Modules\SupplierType\Exceptions\SupplierTypeNotFoundException;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;
use App\Modules\SupplierType\Services\SupplierTypeUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the status and returns the refreshed model', function () {
    $command = new UpdateStatusSupplierTypeCommand(status: 'inactive');
    $model = new SupplierType(['status' => 'active']);

    $repository = Mockery::mock(SupplierTypeRepositoryInterface::class);
    $repository->expects('findById')->with('supplier-type-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('supplier-type-uuid', null)
        ->andReturn(new SupplierType(['status' => 'inactive']));

    $service = new SupplierTypeUpdateStatusService($repository);

    expect($service->execute('supplier-type-uuid', $command)->status)->toBe('inactive');
});

test('it throws when changing the status of a missing supplier type', function () {
    $command = new UpdateStatusSupplierTypeCommand(status: 'inactive');

    $repository = Mockery::mock(SupplierTypeRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new SupplierTypeUpdateStatusService($repository);

    $service->execute('missing-uuid', $command);
})->throws(SupplierTypeNotFoundException::class);
