<?php

declare(strict_types=1);

use App\Modules\ClientCollection\Models\ClientCollectionApplication;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;

/** Filas del reparto que escribió una nota de crédito. */
function salesCreditNoteApplications(SalesCreditNote $note): \Illuminate\Database\Eloquent\Collection
{
    return ClientCollectionApplication::query()
        ->where('source_type', SalesCreditNote::APPLICATION_SOURCE)
        ->where('source_id', $note->id)
        ->orderBy('created_at')
        ->get();
}

test('confirming a note credits the invoice it corrects', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    expect((float) $invoice->total)->toBe(200.0);
    expect((float) $invoice->balance)->toBe(200.0);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 50,
        ]],
    ]);

    expect((float) $note->total)->toBe(50.0);

    /** En borrador la factura sigue debiendo entera. */
    expect((float) $invoice->refresh()->balance)->toBe(200.0);

    moveSalesCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(50.0);
    expect((float) $invoice->balance)->toBe(150.0);
    expect($invoice->payment_status)->toBe('partial');

    $note->refresh();
    expect((float) $note->applied_amount)->toBe(50.0);
    expect((float) $note->balance)->toBe(0.0);
    /** Agotada al aplicarse entera: su estado es `completed`. */
    expect($note->status)->toBe('completed');

    $applications = salesCreditNoteApplications($note);
    expect($applications)->toHaveCount(1);
    expect($applications->first()->source_type)->toBe('credit_note');
    expect($applications->first()->sales_invoice_id)->toBe($invoice->id);
    expect((float) $applications->first()->applied_amount)->toBe(50.0);
    expect($applications->first()->status)->toBe('active');
});

test('the client balance and the sum of his open invoices stay in step', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    expect((float) $client->refresh()->current_balance)->toBe(200.0);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 80,
        ]],
    ]);

    moveSalesCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $client->refresh()->current_balance)->toBe(120.0);
    expect((float) $invoice->refresh()->balance)->toBe(120.0);
});

test('a note without an invoice stays as available credit', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit);

    moveSalesCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    $note->refresh();
    expect($note->status)->toBe('confirmed');
    expect((float) $note->applied_amount)->toBe(0.0);
    expect((float) $note->balance)->toBe((float) $note->total);
    expect(salesCreditNoteApplications($note))->toHaveCount(0);
});

test('a note bigger than the open balance does not overpay the invoice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 30,
        ]],
    ]);

    expect((float) $invoice->balance)->toBe(30.0);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 30,
        ]],
    ]);

    moveSalesCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->balance)->toBe(0.0);
    expect($invoice->payment_status)->toBe('paid');
});

test('a note that already credited an invoice can no longer be cancelled', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesCreditNoteScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
    ]);

    moveSalesCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $invoice->refresh()->balance)->toBe(150.0);
    expect($note->refresh()->status)->toBe('completed');

    /**
     * El crédito ya se gastó en una factura: anular la nota lo desharía por la
     * espalda. Para revertirlo hay que revertir la aplicación.
     */
    moveSalesCreditNoteTo($user, $company, $note, 'cancelled')->assertSessionHasErrors('status');

    expect($note->refresh()->status)->toBe('completed');
    expect((float) $invoice->refresh()->balance)->toBe(150.0);
});

test('a note with no credit spent is still cancellable', function () {
    [$user, $company, $client, , $item, $unit] = salesCreditNoteScenario();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit);

    moveSalesCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();
    moveSalesCreditNoteTo($user, $company, $note, 'cancelled')->assertSessionHasNoErrors();

    expect($note->refresh()->status)->toBe('cancelled');
});
