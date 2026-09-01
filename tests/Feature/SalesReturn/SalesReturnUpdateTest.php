<?php

declare(strict_types=1);

use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;

use function Pest\Laravel\actingAs;

test('a draft sales return can be updated', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $payload = salesReturnPayload($client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = SalesReturn::with('lines')->findOrFail($payload['id']);
    $line = $return->lines->first();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update', ['company' => $company->id, 'id' => $return->id]), [
            ...$payload,
            'notes' => 'Se corrige la cantidad recibida.',
            'lines' => [[
                'id' => $line->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'unit_price' => 25,
            ]],
        ]);

    $response->assertRedirect(route('sales-returns.show', ['company' => $company->id, 'id' => $return->id]));
    $response->assertSessionHasNoErrors();

    $return->refresh()->load('lines');
    expect($return->notes)->toBe('Se corrige la cantidad recibida.');
    expect($return->lines)->toHaveCount(1);
    expect((float) $return->lines->first()->quantity)->toBe(4.0);
    expect((float) $return->subtotal)->toBe(100.0);
    expect((float) $return->total)->toBe(100.0);
});

test('a line that stops being sent is deactivated, never deleted', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $payload = salesReturnPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2, 'unit_price' => 20],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = SalesReturn::with('lines')->findOrFail($payload['id']);
    $kept = $return->lines->firstWhere('line_number', 1);
    $dropped = $return->lines->firstWhere('line_number', 2);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update', ['company' => $company->id, 'id' => $return->id]), [
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

    expect(SalesReturnLine::find($dropped->id)->status)->toBe('inactive');
    expect(SalesReturnLine::find($kept->id)->status)->toBe('active');

    /** Los totales suman solo las activas. */
    expect((float) $return->refresh()->total)->toBe(10.0);
});

test('a new line takes the next free number and does not reuse the inactive one', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $payload = salesReturnPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update', ['company' => $company->id, 'id' => $payload['id']]), [
            ...$payload,
            'lines' => [
                ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 3, 'unit_price' => 10],
            ],
        ])
        ->assertSessionHasNoErrors();

    $lines = SalesReturnLine::where('sales_return_id', $payload['id'])
        ->orderBy('line_number')
        ->get();

    expect($lines)->toHaveCount(2);
    expect($lines[0]->status)->toBe('inactive');
    expect($lines[1]->line_number)->toBe(2);
    expect($lines[1]->status)->toBe('active');
});

test('the return does not compete with itself for the returned quantity', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    /** La factura del escenario trae una línea de 2 unidades. */
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $payload = salesReturnPayload($client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 100,
            'sales_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = SalesReturn::with('lines')->findOrFail($payload['id']);

    /** Volver a guardar las mismas 2 no puede leerse como 4 devueltas. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update', ['company' => $company->id, 'id' => $return->id]), [
            ...$payload,
            'lines' => [[
                'id' => $return->lines->first()->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => 100,
                'sales_invoice_line_id' => $invoiceLine->id,
            ]],
        ])
        ->assertSessionHasNoErrors();

    expect((float) $return->refresh()->total)->toBe(200.0);
});

test('a confirmed return can no longer be edited', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $payload = salesReturnPayload($client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    SalesReturn::where('id', $payload['id'])->update(['status' => 'confirmed']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-returns.update', ['company' => $company->id, 'id' => $payload['id']]),
            [...$payload, 'notes' => 'Ya no se edita.'],
        )
        ->assertSessionHasErrors('status');

    expect(SalesReturn::find($payload['id'])->notes)->toBeNull();
});

test('a user without permission cannot update a sales return', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $payload = salesReturnPayload($client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    assignRoleWithPermissions($user, $company, ['sales-returns.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update', ['company' => $company->id, 'id' => $payload['id']]), $payload)
        ->assertForbidden();
});
