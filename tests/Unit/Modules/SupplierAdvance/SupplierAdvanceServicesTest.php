<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use App\Modules\SupplierAdvance\Commands\SearchSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\UpdateStatusSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Exceptions\SupplierAdvanceNotFoundException;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use App\Modules\SupplierAdvance\Services\SupplierAdvanceFindService;
use App\Modules\SupplierAdvance\Services\SupplierAdvanceOrderService;
use App\Modules\SupplierAdvance\Services\SupplierAdvancePaymentSyncService;
use App\Modules\SupplierAdvance\Services\SupplierAdvanceSearchService;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

/** Un anticipo en memoria, sin tocar la base. */
function advanceDouble(array $attributes = []): SupplierAdvance
{
    $advance = new SupplierAdvance([
        'code' => 'ANP000001',
        'company_id' => 'company-uuid',
        'supplier_id' => 'supplier-uuid',
        'amount' => 400,
        'applied_amount' => 0,
        'balance' => 400,
        'status' => 'pending_confirmation',
        ...$attributes,
    ]);

    $advance->id = $attributes['id'] ?? 'advance-uuid';

    return $advance;
}

/** El pago espejo de ese anticipo, también en memoria. */
function mirrorPaymentDouble(): SupplierPayment
{
    return new SupplierPayment([
        'company_id' => 'company-uuid',
        'supplier_id' => 'supplier-uuid',
        'origin_type' => SupplierPayment::ORIGIN_ADVANCE,
        'origin_id' => 'advance-uuid',
        'amount' => 400,
    ]);
}

/** El servicio de orden con el repositorio de órdenes simulado. */
function orderServiceReturning(?PurchaseOrder $order): SupplierAdvanceOrderService
{
    $repository = Mockery::mock(PurchaseOrderRepositoryInterface::class);
    $repository->allows('findById')->andReturn($order);

    return new SupplierAdvanceOrderService($repository);
}

test('an advance without a purchase order needs no check', function () {
    orderServiceReturning(null)->guardPurchaseOrder(null, 'supplier-uuid', 'company-uuid');
})->throwsNoExceptions();

test('the purchase order has to exist in the company', function () {
    orderServiceReturning(null)->guardPurchaseOrder('order-uuid', 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('the purchase order has to be of the same supplier', function () {
    $order = new PurchaseOrder(['supplier_id' => 'another-supplier']);

    orderServiceReturning($order)->guardPurchaseOrder('order-uuid', 'supplier-uuid', 'company-uuid');
})->throws(ValidationException::class);

test('the find service fails when the advance does not exist', function () {
    $repository = Mockery::mock(SupplierAdvanceRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    (new SupplierAdvanceFindService($repository))->execute('missing-uuid');
})->throws(SupplierAdvanceNotFoundException::class);

test('the search service delegates on the repository', function () {
    $command = new SearchSupplierAdvanceCommand(filters: ['status' => 'confirmed']);

    $repository = Mockery::mock(SupplierAdvanceRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn(['data' => [], 'total' => 0]);

    expect((new SupplierAdvanceSearchService($repository))->execute($command))
        ->toBe(['data' => [], 'total' => 0]);
});

test('confirming the payment moves the advance and credits the supplier', function () {
    $advance = advanceDouble();

    $repository = Mockery::mock(SupplierAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn($advance);
    $repository->expects('updateStatus')->withArgs(
        fn (SupplierAdvance $model, UpdateStatusSupplierAdvanceCommand $command): bool => $command->status === 'confirmed',
    );
    $repository->expects('findOrFail')->andReturn($advance);

    $balances = Mockery::mock(SupplierApplyBalanceService::class);
    $balances->expects('execute')->withArgs(
        fn ($command): bool => $command->advanceBalanceDelta === 400.0,
    )->andReturn(new Supplier);

    (new SupplierAdvancePaymentSyncService($repository, $balances))->confirm(mirrorPaymentDouble());
});

test('an advance that is not waiting for its payment cannot be confirmed', function () {
    $repository = Mockery::mock(SupplierAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn(advanceDouble(['status' => 'draft']));

    $balances = Mockery::mock(SupplierApplyBalanceService::class);

    (new SupplierAdvancePaymentSyncService($repository, $balances))->confirm(mirrorPaymentDouble());
})->throws(ValidationException::class);

test('cancelling a delivered payment takes back the credit of the supplier', function () {
    $advance = advanceDouble(['status' => 'confirmed']);

    $repository = Mockery::mock(SupplierAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn($advance);
    $repository->expects('updateStatus')->withArgs(
        fn (SupplierAdvance $model, UpdateStatusSupplierAdvanceCommand $command): bool => $command->status === 'draft',
    );
    $repository->expects('findOrFail')->andReturn($advance);

    $balances = Mockery::mock(SupplierApplyBalanceService::class);
    $balances->expects('execute')->withArgs(
        fn ($command): bool => $command->advanceBalanceDelta === -400.0,
    )->andReturn(new Supplier);

    (new SupplierAdvancePaymentSyncService($repository, $balances))->revert(mirrorPaymentDouble());
});

/** Comprometido nunca dio crédito: revertirlo no se lo quita a nadie. */
test('cancelling a draft payment moves no balance', function () {
    $advance = advanceDouble();

    $repository = Mockery::mock(SupplierAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn($advance);
    $repository->expects('updateStatus');
    $repository->expects('findOrFail')->andReturn($advance);

    $balances = Mockery::mock(SupplierApplyBalanceService::class);
    $balances->expects('execute')->never();

    (new SupplierAdvancePaymentSyncService($repository, $balances))->revert(mirrorPaymentDouble());
});

test('an advance already applied to invoices cannot be reverted', function () {
    $repository = Mockery::mock(SupplierAdvanceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn(advanceDouble([
        'status' => 'confirmed',
        'applied_amount' => 100,
    ]));

    $balances = Mockery::mock(SupplierApplyBalanceService::class);

    (new SupplierAdvancePaymentSyncService($repository, $balances))->revert(mirrorPaymentDouble());
})->throws(ValidationException::class);
