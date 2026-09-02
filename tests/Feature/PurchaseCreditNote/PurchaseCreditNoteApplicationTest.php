<?php

declare(strict_types=1);

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;

/** Filas del reparto que escribió una nota de crédito a proveedor. */
function purchaseCreditNoteApplications(PurchaseCreditNote $note): \Illuminate\Database\Eloquent\Collection
{
    return SupplierPaymentApplication::query()
        ->where('source_type', PurchaseCreditNote::APPLICATION_SOURCE)
        ->where('source_id', $note->id)
        ->orderBy('created_at')
        ->get();
}

test('confirming a note credits the invoice it corrects', function () {
    [$user, $company, , $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    expect((float) $invoice->total)->toBe(250.0);
    expect((float) $supplier->refresh()->current_balance)->toBe(250.0);

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 25,
        ]],
    ]);

    expect((float) $note->total)->toBe(50.0);
    expect((float) $invoice->refresh()->balance)->toBe(250.0);

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(50.0);
    expect((float) $invoice->balance)->toBe(200.0);
    expect($invoice->payment_status)->toBe('partial');

    /** El saldo del proveedor y la suma de sus facturas abiertas siguen cuadrando. */
    expect((float) $supplier->refresh()->current_balance)->toBe(200.0);

    $note->refresh();
    expect((float) $note->applied_amount)->toBe(50.0);
    expect((float) $note->balance)->toBe(0.0);
    expect($note->status)->toBe('completed');

    $applications = purchaseCreditNoteApplications($note);
    expect($applications)->toHaveCount(1);
    expect($applications->first()->source_type)->toBe('credit_note');
    expect((float) $applications->first()->applied_amount)->toBe(50.0);
});

test('a note without an invoice stays as available credit', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit);

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    $note->refresh();
    expect($note->status)->toBe('confirmed');
    expect((float) $note->applied_amount)->toBe(0.0);
    expect((float) $note->balance)->toBe((float) $note->total);
    expect(purchaseCreditNoteApplications($note))->toHaveCount(0);
});

test('a note bigger than the open balance does not overpay the invoice', function () {
    [$user, $company, , $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 0]);

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 30,
        ]],
    ]);

    expect((float) $invoice->balance)->toBe(30.0);

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 30,
        ]],
    ]);

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->balance)->toBe(0.0);
    expect($invoice->payment_status)->toBe('paid');
});
