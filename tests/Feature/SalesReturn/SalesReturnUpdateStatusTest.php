<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

/** Los movimientos vivos que escribió la devolución, sin contrapartidas. */
function salesReturnMovements(SalesReturn $return): \Illuminate\Database\Eloquent\Collection
{
    return InventoryMovement::query()
        ->where('origin_type', SalesReturn::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $return->id)
        ->orderBy('created_at')
        ->get();
}

test('confirming brings the goods back into the warehouse at the original sale cost', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    /** La bodega tiene existencia, comprada a 20 y revalorada a 40 después. */
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 40]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();
    SalesInvoiceLine::where('id', $invoiceLine->id)->update(['unit_cost' => 20]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'sales_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 100,
            'sales_invoice_line_id' => $invoiceLine->id,
            'location_id' => $location->id,
        ]],
    ]);

    /** El costo con el que vuelve se congela al guardar la línea. */
    expect((float) $return->lines->first()->unit_cost)->toBe(20.0);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('confirmed');

    $movements = salesReturnMovements($return);
    expect($movements)->toHaveCount(1);

    $movement = $movements->first();
    expect($movement->type)->toBe('in');
    expect($movement->warehouse_id)->toBe($warehouse->id);
    expect($movement->location_id)->toBe($location->id);
    expect((float) $movement->quantity)->toBe(2.0);
    /** Entra al costo de la venta (20), no al promedio vigente (30). */
    expect((float) $movement->unit_cost)->toBe(20.0);
    expect((float) $movement->total_cost)->toBe(40.0);
    expect((float) $movement->balance_quantity)->toBe(22.0);

    /** Y la factura apunta lo devuelto. */
    expect((float) SalesInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(2.0);
});

test('the entry is written in the base unit of the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    $box = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()->create(['company_id' => $company->id]);
    \App\Modules\Item\Models\ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 5]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 2,
            'unit_price' => 60,
            'location_id' => $location->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    /** 2 cajas de 12 son 24 unidades base. */
    expect((float) salesReturnMovements($return)->first()->quantity)->toBe(24.0);
});

test('a line without location falls back to the default one of the warehouse', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect(salesReturnMovements($return)->first()->location_id)->toBe($location->id);
});

test('a warehouse without a default location cannot confirm the return', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    WarehouseLocation::where('id', $location->id)->update(['is_default' => 'no']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('draft');
    expect(salesReturnMovements($return))->toHaveCount(0);
});

test('what comes back to be destroyed does not touch the kardex', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'condition' => 'scrap',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    /** El documento vive, pero ninguna existencia se movió: la pérdida va por Ajuste. */
    expect($return->refresh()->status)->toBe('confirmed');
    expect(salesReturnMovements($return))->toHaveCount(0);
});

test('a line marked as scrap stays out of the kardex while the rest comes in', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => 100,
            ],
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 100,
                'condition' => 'scrap',
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $movements = salesReturnMovements($return);
    expect($movements)->toHaveCount(1);
    expect((float) $movements->first()->quantity)->toBe(2.0);
});

test('cancelling a confirmed return takes the goods out again with a counter-entry', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit, [
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
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $return->refresh();
    expect($return->status)->toBe('cancelled');
    expect($return->cancelled_at)->not->toBeNull();

    $movements = salesReturnMovements($return);
    expect($movements)->toHaveCount(2);

    $entry = $movements->firstWhere('type', 'in');
    $exit = $movements->firstWhere('type', 'out');

    /** El original no se borra: queda revertido y apuntado por su contrapartida. */
    expect($entry->status)->toBe('reversed');
    expect($exit->reversal_of_id)->toBe($entry->id);
    expect((float) $exit->quantity)->toBe(2.0);
    expect((float) $exit->balance_quantity)->toBe(10.0);

    /** Y la factura recupera el cupo devuelto. */
    expect((float) SalesInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(0.0);
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
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

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
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

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
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

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
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

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

test('the credit note that credits a return does not move the inventory again', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = salesReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $return = createSalesReturn($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('sales-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    /** La devolución ya metió la mercancía: la nota solo baja la cuenta por cobrar. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-credit-notes.store', ['company' => $company->id]),
            salesCreditNotePayload($client, $item, $unit, [
                'sales_return_id' => $return->id,
                'affects_inventory' => 'yes',
            ]),
        )
        ->assertSessionHasErrors('affects_inventory');

    expect($return->refresh()->credit_note_id)->toBeNull();
});
