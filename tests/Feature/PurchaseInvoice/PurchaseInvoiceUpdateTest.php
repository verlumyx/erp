<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;

use function Pest\Laravel\actingAs;

test('a draft purchase invoice can be updated', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $invoice = PurchaseInvoice::with('lines')->findOrFail($payload['id']);
    $line = $invoice->lines->first();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update', ['company' => $company->id, 'id' => $invoice->id]), [
            ...$payload,
            'supplier_invoice_series' => 'B',
            'lines' => [[
                'id' => $line->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'unit_price' => 25,
            ]],
        ]);

    $response->assertRedirect(route('purchase-invoices.show', ['company' => $company->id, 'id' => $invoice->id]));
    $response->assertSessionHasNoErrors();

    $invoice->refresh()->load('lines');
    expect($invoice->supplier_invoice_series)->toBe('B');
    expect($invoice->lines)->toHaveCount(1);
    expect((float) $invoice->lines->first()->quantity)->toBe(4.0);
    expect((float) $invoice->subtotal)->toBe(100.0);
    expect((float) $invoice->total)->toBe(100.0);
    expect((float) $invoice->balance)->toBe(100.0);
    /** El importe legal en bolívares se rehace con la tasa del documento. */
    expect((float) $invoice->total_ves)->toBe(3650.0);
});

test('keeping its own printed number is not read as a duplicate', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'supplier_invoice_number' => '00-555555',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-invoices.update', ['company' => $company->id, 'id' => $payload['id']]),
            [...$payload, 'notes' => 'Se corrigió la nota.'],
        )
        ->assertSessionHasNoErrors();

    expect(PurchaseInvoice::find($payload['id'])->notes)->toBe('Se corrigió la nota.');
});

test('a line that stops being sent is deactivated, never deleted', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2, 'unit_price' => 20],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $invoice = PurchaseInvoice::with('lines')->findOrFail($payload['id']);
    $kept = $invoice->lines->firstWhere('line_number', 1);
    $dropped = $invoice->lines->firstWhere('line_number', 2);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update', ['company' => $company->id, 'id' => $invoice->id]), [
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

    expect(PurchaseInvoiceLine::find($dropped->id)->status)->toBe('inactive');
    expect(PurchaseInvoiceLine::find($kept->id)->status)->toBe('active');

    /** Los totales suman solo las activas. */
    expect((float) $invoice->refresh()->total)->toBe(10.0);
});

test('a new line takes the next free number and does not reuse the inactive one', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update', ['company' => $company->id, 'id' => $payload['id']]), [
            ...$payload,
            'lines' => [
                ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 3, 'unit_price' => 10],
            ],
        ])
        ->assertSessionHasNoErrors();

    $lines = PurchaseInvoiceLine::where('purchase_invoice_id', $payload['id'])
        ->orderBy('line_number')
        ->get();

    expect($lines)->toHaveCount(2);
    expect($lines[0]->status)->toBe('inactive');
    expect($lines[1]->line_number)->toBe(2);
    expect($lines[1]->status)->toBe('active');
});

test('a confirmed invoice can no longer be edited', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    PurchaseInvoice::where('id', $payload['id'])->update(['status' => 'confirmed']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-invoices.update', ['company' => $company->id, 'id' => $payload['id']]),
            [...$payload, 'supplier_invoice_series' => 'Z'],
        )
        ->assertSessionHasErrors('status');

    expect(PurchaseInvoice::find($payload['id'])->supplier_invoice_series)->toBeNull();
});

test('a user without permission cannot update a purchase invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $payload = purchaseInvoicePayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    assignRoleWithPermissions($user, $company, ['purchase-invoices.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update', ['company' => $company->id, 'id' => $payload['id']]), $payload)
        ->assertForbidden();
});
