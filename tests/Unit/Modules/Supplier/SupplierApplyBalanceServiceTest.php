<?php

declare(strict_types=1);

use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Commands\WriteSupplierBalancesCommand;
use App\Modules\Supplier\Exceptions\SupplierNotFoundException;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;

uses(Tests\TestCase::class);

/**
 * Monta el servicio y devuelve los saldos que le pide escribir al repositorio,
 * que es lo único observable de una afectación resuelta.
 */
function applySupplierBalance(
    Supplier $supplier,
    float $currentDelta,
    float $advanceDelta = 0,
): WriteSupplierBalancesCommand {
    $written = null;

    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->allows('lockById')->andReturn($supplier);
    $repository->allows('writeBalances')->andReturnUsing(
        function (Supplier $model, WriteSupplierBalancesCommand $command) use (&$written): Supplier {
            $written = $command;

            return $model;
        },
    );

    (new SupplierApplyBalanceService($repository))->execute(new ApplySupplierBalanceCommand(
        companyId: 'company-uuid',
        supplierId: 'supplier-uuid',
        currentBalanceDelta: $currentDelta,
        advanceBalanceDelta: $advanceDelta,
    ));

    return $written;
}

test('a payment lowers what the supplier is owed', function () {
    $supplier = new Supplier(['current_balance' => 500, 'advance_balance' => 0]);

    $written = applySupplierBalance($supplier, -200);

    expect($written->currentBalance)->toBe(300.0);
    expect($written->advanceBalance)->toBe(0.0);
});

test('reversing that payment gives the debt back', function () {
    $supplier = new Supplier(['current_balance' => 300, 'advance_balance' => 0]);

    expect(applySupplierBalance($supplier, 200)->currentBalance)->toBe(500.0);
});

test('an advance raises the credit in favour of the supplier', function () {
    $supplier = new Supplier(['current_balance' => 0, 'advance_balance' => 100]);

    expect(applySupplierBalance($supplier, 0, 50)->advanceBalance)->toBe(150.0);
});

test('moving the balance of a supplier that does not exist fails', function () {
    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('lockById')->andReturn(null);

    (new SupplierApplyBalanceService($repository))->execute(new ApplySupplierBalanceCommand(
        companyId: 'company-uuid',
        supplierId: 'missing-uuid',
        currentBalanceDelta: -10,
    ));
})->throws(SupplierNotFoundException::class);
