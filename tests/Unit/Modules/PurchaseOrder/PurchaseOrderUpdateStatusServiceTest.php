<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Commands\UpdateStatusPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\PurchaseOrder\Services\PurchaseOrderUpdateStatusService;

uses(Tests\TestCase::class);

test('it changes the status and returns the fresh model', function () {
    $command = new UpdateStatusPurchaseOrderCommand(status: 'confirmed', approvedBy: 'user-uuid');
    $model = new PurchaseOrder(['id' => 'order-uuid', 'status' => 'draft']);

    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('findById')->with('order-uuid', 'company-uuid')->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('order-uuid', 'company-uuid')
        ->andReturn(new PurchaseOrder(['id' => 'order-uuid', 'status' => 'confirmed']));

    $service = new PurchaseOrderUpdateStatusService($repository);

    expect($service->execute('order-uuid', $command, 'company-uuid')->status)->toBe('confirmed');
});

test('it throws when the order does not exist', function () {
    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new PurchaseOrderUpdateStatusService($repository);

    $service->execute('missing-uuid', new UpdateStatusPurchaseOrderCommand(status: 'confirmed'));
})->throws(PurchaseOrderNotFoundException::class);
