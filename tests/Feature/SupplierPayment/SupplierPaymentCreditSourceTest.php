<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;

/** Filas del reparto de un origen cualquiera. */
function paymentApplicationsOfSource(string $sourceType, string $sourceId): \Illuminate\Database\Eloquent\Collection
{
    return SupplierPaymentApplication::query()
        ->where('source_type', $sourceType)
        ->where('source_id', $sourceId)
        ->orderBy('created_at')
        ->get();
}

test('a payment made with an advance spends the credit with the supplier', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    expect((float) $invoice->balance)->toBe(250.0);

    $advance = confirmedSupplierAdvance($user, $company, $supplier, ['amount' => 400]);

    expect($advance->status)->toBe('confirmed');
    expect((float) $supplier->refresh()->advance_balance)->toBe(400.0);
    expect((float) $supplier->current_balance)->toBe(250.0);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'payment_method' => 'advance',
        'credit_source_id' => $advance->id,
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    /** El reparto viaja con el `source_type` del crédito, no con el del pago. */
    expect(paymentApplicationsOfSource('advance', $advance->id))->toHaveCount(1);
    expect(paymentApplicationsOfSource('payment', $payment->id))->toHaveCount(0);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->balance)->toBe(0.0);
    expect($invoice->payment_status)->toBe('paid');

    $advance->refresh();
    expect((float) $advance->applied_amount)->toBe(250.0);
    expect((float) $advance->balance)->toBe(150.0);
    expect($advance->status)->toBe('partial');

    $supplier->refresh();
    expect((float) $supplier->current_balance)->toBe(0.0);
    expect((float) $supplier->advance_balance)->toBe(150.0);
});

test('a payment cannot spend more credit than the advance has left', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $advance = confirmedSupplierAdvance($user, $company, $supplier, ['amount' => 50]);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-payments.store', ['company' => $company->id]), supplierPaymentPayload($supplier, [
            'payment_method' => 'advance',
            'credit_source_id' => $advance->id,
            'amount' => 250,
            'applications' => [
                ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
            ],
        ]))
        ->assertSessionHasErrors('credit_source_id');
});

test('a payment made with credit has to say which credit', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-payments.store', ['company' => $company->id]), supplierPaymentPayload($supplier, [
            'payment_method' => 'credit_note',
        ]))
        ->assertSessionHasErrors('credit_source_id');
});

test('cancelling a payment gives the advance its credit back', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $advance = confirmedSupplierAdvance($user, $company, $supplier, ['amount' => 400]);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'payment_method' => 'advance',
        'credit_source_id' => $advance->id,
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    moveSupplierPaymentTo($user, $company, $payment, 'cancelled', [
        'cancellation_reason' => 'Se aplicó al anticipo equivocado.',
    ])->assertSessionHasNoErrors();

    expect((float) $invoice->refresh()->balance)->toBe(250.0);

    $advance->refresh();
    expect((float) $advance->applied_amount)->toBe(0.0);
    expect((float) $advance->balance)->toBe(400.0);
    expect($advance->status)->toBe('confirmed');

    $supplier->refresh();
    expect((float) $supplier->current_balance)->toBe(250.0);
    expect((float) $supplier->advance_balance)->toBe(400.0);
});

test('a payment made with a credit note does not lower the payable twice', function () {
    [$user, $company, , $warehouse, $item, $unit] = supplierPaymentScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $first = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $second = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    expect((float) $supplier->refresh()->current_balance)->toBe(500.0);

    /** Una nota suelta: baja la deuda con el proveedor y queda como crédito. */
    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 30,
        ]],
    ]);

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $supplier->refresh()->current_balance)->toBe(440.0);
    expect((float) $note->refresh()->balance)->toBe(60.0);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'payment_method' => 'credit_note',
        'credit_source_id' => $note->id,
        'amount' => 60,
        'applications' => [
            ['purchase_invoice_id' => $second->id, 'applied_amount' => 60],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $second->refresh()->balance)->toBe(190.0);
    expect((float) $first->refresh()->balance)->toBe(250.0);

    /** La nota ya la había bajado: aplicarla no vuelve a bajarla. */
    expect((float) $supplier->refresh()->current_balance)->toBe(440.0);

    $note->refresh();
    expect((float) $note->applied_amount)->toBe(60.0);
    expect($note->status)->toBe('completed');
});
