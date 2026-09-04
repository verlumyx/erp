<?php

declare(strict_types=1);

use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;

use function Pest\Laravel\actingAs;

test('a draft purchase return can be updated', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    /** La factura del escenario trae una línea de 10 unidades a 25. */
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'warehouse_id' => $warehouse->id,
            'purchase_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = PurchaseReturn::with('lines')->findOrFail($payload['id']);
    $line = $return->lines->first();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update', ['company' => $company->id, 'id' => $return->id]), [
            ...$payload,
            'tracking_number' => 'ZM-000999',
            'lines' => [[
                'id' => $line->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'warehouse_id' => $warehouse->id,
                'purchase_invoice_line_id' => $invoiceLine->id,
            ]],
        ]);

    $response->assertRedirect(route('purchase-returns.show', ['company' => $company->id, 'id' => $return->id]));
    $response->assertSessionHasNoErrors();

    $return->refresh()->load('lines');
    expect($return->tracking_number)->toBe('ZM-000999');
    expect($return->lines)->toHaveCount(1);
    expect((float) $return->lines->first()->quantity)->toBe(4.0);
    /** El costo lo pone la factura: 4 × 25. */
    expect((float) $return->subtotal)->toBe(100.0);
    expect((float) $return->total)->toBe(100.0);
});

test('a line that stops being sent is deactivated, never deleted', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    /** La factura del escenario trae una línea de 10 unidades a 25. */
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $line = fn (float $quantity): array => [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => $quantity,
        'warehouse_id' => $warehouse->id,
        'purchase_invoice_line_id' => $invoiceLine->id,
    ];

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [$line(1), $line(2)],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = PurchaseReturn::with('lines')->findOrFail($payload['id']);
    $kept = $return->lines->firstWhere('line_number', 1);
    $dropped = $return->lines->firstWhere('line_number', 2);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update', ['company' => $company->id, 'id' => $return->id]), [
            ...$payload,
            'lines' => [[...$line(1), 'id' => $kept->id]],
        ])
        ->assertSessionHasNoErrors();

    expect(PurchaseReturnLine::find($dropped->id)->status)->toBe('inactive');
    expect(PurchaseReturnLine::find($kept->id)->status)->toBe('active');

    /** Los totales suman solo las activas: 1 × 25. */
    expect((float) $return->refresh()->total)->toBe(25.0);
});

test('a new line takes the next free number and does not reuse the inactive one', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'warehouse_id' => $warehouse->id],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update', ['company' => $company->id, 'id' => $payload['id']]), [
            ...$payload,
            'lines' => [
                ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 3, 'warehouse_id' => $warehouse->id],
            ],
        ])
        ->assertSessionHasNoErrors();

    $lines = PurchaseReturnLine::where('purchase_return_id', $payload['id'])
        ->orderBy('line_number')
        ->get();

    expect($lines)->toHaveCount(2);
    expect($lines[0]->status)->toBe('inactive');
    expect($lines[1]->line_number)->toBe(2);
    expect($lines[1]->status)->toBe('active');
});

test('the return does not compete with itself for the returned quantity', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    /** La factura del escenario trae una línea de 10 unidades. */
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'warehouse_id' => $warehouse->id,
            'purchase_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = PurchaseReturn::with('lines')->findOrFail($payload['id']);

    /** Volver a guardar las mismas 10 no puede leerse como 20 devueltas. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update', ['company' => $company->id, 'id' => $return->id]), [
            ...$payload,
            'lines' => [[
                'id' => $return->lines->first()->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'warehouse_id' => $warehouse->id,
                'purchase_invoice_line_id' => $invoiceLine->id,
            ]],
        ])
        ->assertSessionHasNoErrors();

    expect((float) $return->refresh()->total)->toBe(250.0);
});

test('a confirmed return can no longer be edited', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    PurchaseReturn::where('id', $payload['id'])->update(['status' => 'confirmed']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-returns.update', ['company' => $company->id, 'id' => $payload['id']]),
            [...$payload, 'tracking_number' => 'ZM-XXX'],
        )
        ->assertSessionHasErrors('status');

    expect(PurchaseReturn::find($payload['id'])->tracking_number)->toBeNull();
});

test('a user without permission cannot update a purchase return', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    assignRoleWithPermissions($user, $company, ['purchase-returns.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update', ['company' => $company->id, 'id' => $payload['id']]), $payload)
        ->assertForbidden();
});
