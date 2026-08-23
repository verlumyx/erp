<?php

declare(strict_types=1);

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNoteLine;

use function Pest\Laravel\actingAs;

test('a draft purchase credit note can be updated', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $note = PurchaseCreditNote::with('lines')->findOrFail($payload['id']);
    $line = $note->lines->first();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update', ['company' => $company->id, 'id' => $note->id]), [
            ...$payload,
            'supplier_document_number' => 'NC-000999',
            'lines' => [[
                'id' => $line->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'unit_price' => 25,
            ]],
        ]);

    $response->assertRedirect(route('purchase-credit-notes.show', ['company' => $company->id, 'id' => $note->id]));
    $response->assertSessionHasNoErrors();

    $note->refresh()->load('lines');
    expect($note->supplier_document_number)->toBe('NC-000999');
    expect($note->lines)->toHaveCount(1);
    expect((float) $note->lines->first()->quantity)->toBe(4.0);
    expect((float) $note->subtotal)->toBe(100.0);
    expect((float) $note->total)->toBe(100.0);
    expect((float) $note->balance)->toBe(100.0);
    /** El importe fiscal en bolívares se rehace con la tasa del documento. */
    expect((float) $note->total_ves)->toBe(3650.0);
});

test('a line that stops being sent is deactivated, never deleted', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2, 'unit_price' => 20],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $note = PurchaseCreditNote::with('lines')->findOrFail($payload['id']);
    $kept = $note->lines->firstWhere('line_number', 1);
    $dropped = $note->lines->firstWhere('line_number', 2);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update', ['company' => $company->id, 'id' => $note->id]), [
            ...$payload,
            'lines' => [[
                'id' => $kept->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 10,
            ]],
        ])
        ->assertSessionHasNoErrors();

    expect(PurchaseCreditNoteLine::find($dropped->id)->status)->toBe('inactive');
    expect(PurchaseCreditNoteLine::find($kept->id)->status)->toBe('active');

    /** Los totales suman solo las activas. */
    expect((float) $note->refresh()->total)->toBe(10.0);
});

test('a new line takes the next free number and does not reuse the inactive one', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update', ['company' => $company->id, 'id' => $payload['id']]), [
            ...$payload,
            'lines' => [
                ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 3, 'unit_price' => 10],
            ],
        ])
        ->assertSessionHasNoErrors();

    $lines = PurchaseCreditNoteLine::where('purchase_credit_note_id', $payload['id'])
        ->orderBy('line_number')
        ->get();

    expect($lines)->toHaveCount(2);
    expect($lines[0]->status)->toBe('inactive');
    expect($lines[1]->line_number)->toBe(2);
    expect($lines[1]->status)->toBe('active');
});

test('the note does not compete with itself for the credited quantity', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseCreditNoteScenario();

    /** La factura del escenario trae una línea de 10 unidades. */
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
            'purchase_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $note = PurchaseCreditNote::with('lines')->findOrFail($payload['id']);

    /** Volver a guardar las mismas 10 no puede leerse como 20 acreditadas. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update', ['company' => $company->id, 'id' => $note->id]), [
            ...$payload,
            'lines' => [[
                'id' => $note->lines->first()->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 25,
                'purchase_invoice_line_id' => $invoiceLine->id,
            ]],
        ])
        ->assertSessionHasNoErrors();

    expect((float) $note->refresh()->total)->toBe(250.0);
});

test('a confirmed note can no longer be edited', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    PurchaseCreditNote::where('id', $payload['id'])->update(['status' => 'confirmed']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-credit-notes.update', ['company' => $company->id, 'id' => $payload['id']]),
            [...$payload, 'supplier_document_number' => 'NC-XXX'],
        )
        ->assertSessionHasErrors('status');

    expect(PurchaseCreditNote::find($payload['id'])->supplier_document_number)->toBeNull();
});

test('a user without permission cannot update a purchase credit note', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseCreditNoteScenario();

    $payload = purchaseCreditNotePayload($supplier, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-credit-notes.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    assignRoleWithPermissions($user, $company, ['purchase-credit-notes.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update', ['company' => $company->id, 'id' => $payload['id']]), $payload)
        ->assertForbidden();
});
