<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;

test('confirming a payment settles its invoices and lowers what the supplier is owed', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    /** La deuda no se siembra: la cargan las dos facturas al confirmarse. */
    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $first = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $second = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    expect((float) $supplier->refresh()->current_balance)->toBe(500.0);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 300,
        'applications' => [
            ['purchase_invoice_id' => $first->id, 'applied_amount' => 250],
            ['purchase_invoice_id' => $second->id, 'applied_amount' => 50],
        ],
    ]);

    $response = moveSupplierPaymentTo($user, $company, $payment, 'confirmed');

    $response->assertRedirect(route('supplier-payments.show', ['company' => $company->id, 'id' => $payment->id]));
    $response->assertSessionHasNoErrors();

    expect($payment->refresh()->status)->toBe('confirmed');

    $first->refresh();
    expect((float) $first->paid_amount)->toBe(250.0);
    expect((float) $first->balance)->toBe(0.0);
    expect($first->payment_status)->toBe('paid');

    $second->refresh();
    expect((float) $second->paid_amount)->toBe(50.0);
    expect((float) $second->balance)->toBe(200.0);
    expect($second->payment_status)->toBe('partial');

    expect((float) $supplier->refresh()->current_balance)->toBe(200.0);
});

test('confirming stamps the exchange difference against the rate of each invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    /** La factura congela 36,50 el día que se emite. */
    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    expect((float) $invoice->exchange_rate)->toBe(36.5);

    /** El pago se registra otro día, con la tasa en 38,00. */
    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 38.0]);

    app()->forgetScopedInstances();

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    expect((float) $payment->exchange_rate)->toBe(38.0);
    expect((float) $payment->amount_ves)->toBe(9500.0);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    $application = SupplierPaymentApplication::where('source_id', $payment->id)->firstOrFail();
    expect((float) $application->exchange_rate)->toBe(38.0);
    expect((float) $application->exchange_difference)->toBe(375.0);
    expect($application->status)->toBe('active');
});

test('confirming does not recalculate the frozen rate', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);
    expect((float) $payment->exchange_rate)->toBe(36.5);

    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 99.0]);

    app()->forgetScopedInstances();

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    $payment->refresh();
    expect((float) $payment->exchange_rate)->toBe(36.5);
    expect((float) $payment->amount_ves)->toBe(9125.0);
});

test('an invoice already settled by another payment blocks the confirmation', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $first = createSupplierPayment($user, $company, $supplier, [
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    $second = createSupplierPayment($user, $company, $supplier, [
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $first, 'confirmed')->assertSessionHasNoErrors();

    moveSupplierPaymentTo($user, $company, $second, 'confirmed')
        ->assertSessionHasErrors('applications.0.purchase_invoice_id');

    expect($second->refresh()->status)->toBe('draft');
    expect((float) $invoice->refresh()->paid_amount)->toBe(250.0);
});

test('cancelling a confirmed payment gives the balance back', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    /** La deuda no se siembra: la carga la factura al confirmarse. */
    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();
    expect((float) $supplier->refresh()->current_balance)->toBe(0.0);

    moveSupplierPaymentTo($user, $company, $payment, 'cancelled', [
        'cancellation_reason' => 'La transferencia fue rechazada por el banco.',
    ])->assertSessionHasNoErrors();

    $payment->refresh();
    expect($payment->status)->toBe('cancelled');
    expect($payment->cancelled_at)->not->toBeNull();

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(0.0);
    expect((float) $invoice->balance)->toBe(250.0);
    expect($invoice->payment_status)->toBe('pending');

    expect((float) $supplier->refresh()->current_balance)->toBe(250.0);

    /** La fila no se borra: queda como revertida. */
    $application = SupplierPaymentApplication::where('source_id', $payment->id)->firstOrFail();
    expect($application->status)->toBe('reversed');
});

test('cancelling a draft payment reverses nothing', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'cancelled', [
        'cancellation_reason' => 'Se registró dos veces.',
    ])->assertSessionHasNoErrors();

    expect($payment->refresh()->status)->toBe('cancelled');
    expect((float) $invoice->refresh()->paid_amount)->toBe(0.0);
});

test('cancelling requires a reason', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    moveSupplierPaymentTo($user, $company, $payment, 'cancelled')
        ->assertSessionHasErrors('cancellation_reason');

    expect($payment->refresh()->status)->toBe('draft');
});

test('a forbidden transition is rejected', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    moveSupplierPaymentTo($user, $company, $payment, 'completed')
        ->assertSessionHasErrors('status');

    expect($payment->refresh()->status)->toBe('draft');
});

test('a cancelled payment is a dead end', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = SupplierPayment::factory()->cancelled()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')
        ->assertSessionHasErrors('status');

    expect($payment->refresh()->status)->toBe('cancelled');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    assignRoleWithPermissions($user, $company, ['supplier-payments.list']);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertForbidden();
});
