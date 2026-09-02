<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierPayment\Models\SupplierPayment;

/** El anticipo que nació del excedente de un pago. */
function surplusSupplierAdvanceOf(SupplierPayment $payment): ?SupplierAdvance
{
    return SupplierAdvance::query()
        ->where('origin_payment_id', $payment->id)
        ->first();
}

test('what is paid and not distributed becomes a confirmed advance', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    expect((float) $invoice->balance)->toBe(250.0);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 300,
        'reference' => 'TRF-55110',
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    expect((float) $payment->unapplied_amount)->toBe(50.0);
    expect(surplusSupplierAdvanceOf($payment))->toBeNull();

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    $advance = surplusSupplierAdvanceOf($payment);
    expect($advance)->not->toBeNull();
    expect($advance->code)->toStartWith('ANP');
    expect($advance->status)->toBe('confirmed');
    expect((float) $advance->amount)->toBe(50.0);
    expect((float) $advance->balance)->toBe(50.0);
    expect($advance->reference)->toBe('TRF-55110');

    /** Y no genera un segundo `PGP`: el dinero ya salió con este. */
    expect(SupplierPayment::query()->where('origin_id', $advance->id)->count())->toBe(0);

    $supplier->refresh();
    expect((float) $supplier->advance_balance)->toBe(50.0);
    expect((float) $supplier->current_balance)->toBe(0.0);
});

test('a payment that distributes everything generates no advance', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    expect(surplusSupplierAdvanceOf($payment))->toBeNull();
    expect((float) $supplier->refresh()->advance_balance)->toBe(0.0);
});

test('the mirror payment of an advance is exempt', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $advance = confirmedSupplierAdvance($user, $company, $supplier, ['amount' => 400]);

    $mirror = SupplierPayment::query()
        ->where('origin_type', 'advance')
        ->where('origin_id', $advance->id)
        ->firstOrFail();

    expect((float) $mirror->unapplied_amount)->toBe(400.0);
    expect(surplusSupplierAdvanceOf($mirror))->toBeNull();

    expect((float) $supplier->refresh()->advance_balance)->toBe(400.0);
});

test('cancelling the payment cancels the advance it generated', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 300,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();
    expect((float) $supplier->refresh()->advance_balance)->toBe(50.0);

    moveSupplierPaymentTo($user, $company, $payment, 'cancelled', [
        'cancellation_reason' => 'La transferencia fue rechazada por el banco.',
    ])->assertSessionHasNoErrors();

    $advance = surplusSupplierAdvanceOf($payment);
    expect($advance->status)->toBe('cancelled');

    $supplier->refresh();
    expect((float) $supplier->advance_balance)->toBe(0.0);
    expect((float) $supplier->current_balance)->toBe(250.0);
});
