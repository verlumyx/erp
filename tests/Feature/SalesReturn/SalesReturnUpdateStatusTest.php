<?php

declare(strict_types=1);

use App\Modules\Entry\Models\Entry;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;

use function Pest\Laravel\actingAs;

/**
 * Los movimientos de kardex que apuntan a la devolución. Debe estar siempre
 * vacía: la devolución no escribe en el kardex, ese reingreso lo asienta la
 * Entrada que recibe la mercancía.
 */
function salesReturnMovements(SalesReturn $return): \Illuminate\Database\Eloquent\Collection
{
    return InventoryMovement::query()
        ->where('origin_id', $return->id)
        ->orderBy('created_at')
        ->get();
}

test('confirming marks on the invoice line what the client gave back', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();
    SalesInvoiceLine::where('id', $invoiceLine->id)->update(['unit_cost' => 20]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'warehouse_id' => $warehouse->id,
            'sales_invoice_line_id' => $invoiceLine->id,
            'location_id' => $location->id,
        ]],
    ]);

    /** El costo de la venta se congela al guardar la línea: con él reingresará la Entrada. */
    expect((float) $return->lines->first()->unit_cost)->toBe(20.0);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('confirmed');

    /** Confirmar consume el cupo de la factura, que es lo que habilita la nota de crédito. */
    expect((float) SalesInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(2.0);
});

test('confirming a return writes nothing into the kardex', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'warehouse_id' => $warehouse->id,
            ],
            /** Lo que vuelve para destruirse: antes era el caso especial, hoy no se distingue. */
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'warehouse_id' => $warehouse->id,
                'condition' => 'scrap',
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('confirmed');

    /** Ninguna línea reingresa: ni la sana ni la de desecho. */
    expect(salesReturnMovements($return))->toHaveCount(0);

    /** El kardex sigue teniendo solo el movimiento con el que se sembró la existencia. */
    expect(InventoryMovement::query()->where('company_id', $company->id)->count())->toBe(1);

    $stock = ItemStock::query()->where('company_id', $company->id)->where('item_id', $item->id)->first();
    expect((float) $stock->quantity)->toBe(10.0);
    expect((float) $stock->average_cost)->toBe(5.0);
});

test('cancelling a confirmed return gives the invoice back the returned quota', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'warehouse_id' => $warehouse->id,
            'sales_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect((float) SalesInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(2.0);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $return->refresh();
    expect($return->status)->toBe('cancelled');
    expect($return->cancelled_at)->not->toBeNull();

    /** La factura recupera el cupo: lo devuelto vuelve a poder devolverse. */
    expect((float) SalesInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(0.0);

    /** Ni al confirmar ni al anular hubo nada que asentar. */
    expect(salesReturnMovements($return))->toHaveCount(0);
});

test('cancelling a draft moves nothing', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('cancelled');
    expect(salesReturnMovements($return))->toHaveCount(0);
});

test('a confirmed return can be completed once its credit note exists', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $return = SalesReturn::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('completed');
});

test('confirming does not recalculate the frozen rate', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    /** La tasa del día cambia después de emitir: la devolución no la estrena. */
    \App\Modules\ExchangeRate\Models\ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 99.0]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect((float) $return->refresh()->exchange_rate)->toBe(36.5);
});

test('a return already credited cannot be cancelled', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $note = SalesCreditNote::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    $return = SalesReturn::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'credit_note_id' => $note->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('confirmed');
});

test('a forbidden transition is rejected', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $return = SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('draft');
});

test('a cancelled return is a dead end', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $return = SalesReturn::factory()->cancelled()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('cancelled');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $client, $warehouse] = salesReturnScenario();

    $return = SalesReturn::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    assignRoleWithPermissions($user, $company, ['sales-returns.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertForbidden();
});

test('a credit note pointing at the return marks it as credited', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_return_id' => $return->id,
    ]);

    $return->refresh();
    expect($return->credit_note_id)->toBe($note->id);
    expect($return->status)->toBe('completed');
});

test('cancelling the credit note gives the return back to confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $note = createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_return_id' => $return->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $return->refresh();
    expect($return->credit_note_id)->toBeNull();
    expect($return->status)->toBe('confirmed');
});

test('a draft return cannot be credited yet', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_return_id' => $return->id,
            ]),
        )
        ->assertSessionHasErrors('sales_return_id');

    expect($return->refresh()->credit_note_id)->toBeNull();
});

test('a return does not take a second live credit note', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    createSalesCreditNote($user, $company, $client, $item, $unit, [
        'sales_return_id' => $return->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_return_id' => $return->id,
            ]),
        )
        ->assertSessionHasErrors('sales_return_id');
});

test('a return of another company cannot be credited', function () {
    [$user, $company, $client, , $item, $unit] = salesReturnScenario();

    $foreign = SalesReturn::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_return_id' => $foreign->id,
            ]),
        )
        ->assertSessionHasErrors('sales_return_id');
});

test('confirming generates the entry that will take the goods back in', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();
    SalesInvoiceLine::where('id', $invoiceLine->id)->update(['unit_cost' => 33]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'warehouse_id' => $warehouse->id,
            'sales_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $entry = Entry::with('lines')->where('sourceable_id', $return->id)->firstOrFail();

    expect($entry->status)->toBe('draft');
    expect($entry->sourceable_type)->toBe(SalesReturn::MORPH_ALIAS);
    expect($entry->entry_type)->toBe(Entry::RETURN_TYPE);
    /** Lo que vuelve de un cliente no se le compra a nadie. */
    expect($entry->supplier_id)->toBeNull();
    expect($entry->warehouse_id)->toBe($warehouse->id);

    expect($entry->lines)->toHaveCount(1);
    $line = $entry->lines->first();
    expect($line->item_id)->toBe($item->id);
    expect((float) $line->quantity)->toBe(2.0);
    /** Reingresa al costo congelado en la venta, no al promedio vigente. */
    expect((float) $line->unit_cost)->toBe(33.0);
    expect($line->sourceable_type)->toBe(SalesReturnLine::MORPH_ALIAS);
    expect($line->sourceable_id)->toBe($return->lines->first()->id);
});

test('what comes back to be destroyed generates no entry', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'condition' => 'scrap',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    /** El `scrap` no reingresa a ninguna bodega: su pérdida se registra por Ajuste. */
    expect(Entry::where('sourceable_id', $return->id)->count())->toBe(0);
});

test('cancelling the return cancels its draft entry', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $entry = Entry::where('sourceable_id', $return->id)->firstOrFail();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    expect($entry->refresh()->status)->toBe('cancelled');
});

test('a return whose entry already came in cannot be cancelled', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesReturnScenario();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $entry = Entry::where('sourceable_id', $return->id)->firstOrFail();
    Entry::where('id', $entry->id)->update(['status' => 'confirmed']);

    /** Primero se anula la entrada, que es la que sabe deshacer su asiento. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('confirmed');
});
