<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Commands\ApplyPurchaseInvoicePaymentCommand;
use App\Modules\PurchaseInvoice\Commands\WritePurchaseInvoicePaymentCommand;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceNotFoundException;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceOverpaidException;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceApplyPaymentService;

uses(Tests\TestCase::class);

/** Una factura en memoria, sin tocar la base. */
function payableInvoice(array $attributes = []): PurchaseInvoice
{
    $invoice = new PurchaseInvoice([
        'total' => 250,
        'paid_amount' => 0,
        'balance' => 250,
        'payment_status' => 'pending',
        'due_date' => now()->addDays(30)->toDateString(),
        ...$attributes,
    ]);

    $invoice->id = 'invoice-uuid';

    return $invoice;
}

/**
 * Monta el servicio y devuelve lo que le pide escribir al repositorio, que es
 * lo único observable de un saldo resuelto.
 */
function applyPayment(PurchaseInvoice $invoice, float $delta): WritePurchaseInvoicePaymentCommand
{
    $written = null;

    $repository = Mockery::mock(PurchaseInvoiceRepositoryInterface::class);
    $repository->allows('lockById')->andReturn($invoice);
    $repository->allows('writePayment')->andReturnUsing(
        function (PurchaseInvoice $model, WritePurchaseInvoicePaymentCommand $command) use (&$written): PurchaseInvoice {
            $written = $command;

            return $model;
        },
    );

    (new PurchaseInvoiceApplyPaymentService($repository))->execute(
        new ApplyPurchaseInvoicePaymentCommand('company-uuid', 'invoice-uuid', $delta),
    );

    return $written;
}

test('a partial payment leaves the invoice as partly paid', function () {
    $written = applyPayment(payableInvoice(), 100);

    expect($written->paidAmount)->toBe(100.0);
    expect($written->balance)->toBe(150.0);
    expect($written->paymentStatus)->toBe('partial');
});

test('a payment for the whole balance settles the invoice', function () {
    $written = applyPayment(payableInvoice(), 250);

    expect($written->paidAmount)->toBe(250.0);
    expect($written->balance)->toBe(0.0);
    expect($written->paymentStatus)->toBe('paid');
});

test('reversing a payment gives the balance back', function () {
    $invoice = payableInvoice(['paid_amount' => 250, 'balance' => 0, 'payment_status' => 'paid']);

    $written = applyPayment($invoice, -250);

    expect($written->paidAmount)->toBe(0.0);
    expect($written->balance)->toBe(250.0);
    expect($written->paymentStatus)->toBe('pending');
});

test('an invoice past its due date stays overdue while it owes something', function () {
    $invoice = payableInvoice(['due_date' => now()->subDay()->toDateString()]);

    expect(applyPayment($invoice, 100)->paymentStatus)->toBe('overdue');
});

test('an invoice due today is not overdue yet', function () {
    $invoice = payableInvoice(['due_date' => now()->toDateString()]);

    expect(applyPayment($invoice, 100)->paymentStatus)->toBe('partial');
});

test('applying more than the invoice owes is rejected', function () {
    applyPayment(payableInvoice(), 300);
})->throws(PurchaseInvoiceOverpaidException::class);

test('reversing more than the invoice was paid is rejected', function () {
    applyPayment(payableInvoice(), -10);
})->throws(PurchaseInvoiceOverpaidException::class);

test('applying to an invoice that does not exist fails', function () {
    $repository = Mockery::mock(PurchaseInvoiceRepositoryInterface::class);
    $repository->expects('lockById')->andReturn(null);

    (new PurchaseInvoiceApplyPaymentService($repository))->execute(
        new ApplyPurchaseInvoicePaymentCommand('company-uuid', 'missing-uuid', 10),
    );
})->throws(PurchaseInvoiceNotFoundException::class);
