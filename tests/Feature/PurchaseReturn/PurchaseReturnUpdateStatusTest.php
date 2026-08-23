<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

/** Los movimientos vivos que escribió la devolución, sin contrapartidas. */
function returnMovements(PurchaseReturn $return): \Illuminate\Database\Eloquent\Collection
{
    return InventoryMovement::query()
        ->where('origin_type', PurchaseReturn::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $return->id)
        ->orderBy('created_at')
        ->get();
}

test('confirming takes the goods out of the warehouse at the original purchase cost', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    /** La bodega tiene existencia, comprada a 20 y revalorada a 30 después. */
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 40]);

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();
    PurchaseInvoiceLine::where('id', $invoiceLine->id)->update(['landed_cost' => 20]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'unit_price' => 25,
            'purchase_invoice_line_id' => $invoiceLine->id,
            'location_id' => $location->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('confirmed');

    $movements = returnMovements($return);
    expect($movements)->toHaveCount(1);

    $movement = $movements->first();
    expect($movement->type)->toBe('out');
    expect($movement->warehouse_id)->toBe($warehouse->id);
    expect($movement->location_id)->toBe($location->id);
    expect((float) $movement->quantity)->toBe(4.0);
    /** Sale al costo de la compra (20), no al promedio vigente (30). */
    expect((float) $movement->unit_cost)->toBe(20.0);
    expect((float) $movement->total_cost)->toBe(80.0);
    expect((float) $movement->balance_quantity)->toBe(16.0);

    /** Y la factura apunta lo devuelto. */
    expect((float) PurchaseInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(4.0);
});

test('the exit is written in the base unit of the item', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $box = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()->create(['company_id' => $company->id]);
    \App\Modules\Item\Models\ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 5]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 2,
            'unit_price' => 60,
            'location_id' => $location->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    /** 2 cajas de 12 son 24 unidades base. */
    expect((float) returnMovements($return)->first()->quantity)->toBe(24.0);
});

test('a line without location falls back to the default one of the warehouse', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect(returnMovements($return)->first()->location_id)->toBe($location->id);
});

test('a warehouse without a default location cannot confirm the return', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    WarehouseLocation::where('id', $location->id)->update(['is_default' => 'no']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('draft');
    expect(returnMovements($return))->toHaveCount(0);
});

test('a warehouse without enough stock cannot confirm the return', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 1, 'unitCost' => 5]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    $return->refresh();
    expect($return->status)->toBe('draft');
    /** La transacción cae entera: ni kardex ni saldo quedan a medias. */
    expect(returnMovements($return))->toHaveCount(0);
});

test('cancelling a confirmed return brings the goods back with a counter-entry', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $invoiceLine = $invoice->lines->first();

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit, [
        'purchase_invoice_id' => $invoice->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'unit_price' => 25,
            'purchase_invoice_line_id' => $invoiceLine->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $return->refresh();
    expect($return->status)->toBe('cancelled');
    expect($return->cancelled_at)->not->toBeNull();

    $movements = returnMovements($return);
    expect($movements)->toHaveCount(2);

    $exit = $movements->firstWhere('type', 'out');
    $entry = $movements->firstWhere('type', 'in');

    /** El original no se borra: queda revertido y apuntado por su contrapartida. */
    expect($exit->status)->toBe('reversed');
    expect($entry->reversal_of_id)->toBe($exit->id);
    expect((float) $entry->quantity)->toBe(4.0);
    expect((float) $entry->balance_quantity)->toBe(10.0);

    /** Y la factura recupera el cupo devuelto. */
    expect((float) PurchaseInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(0.0);
});

test('cancelling a draft moves nothing', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('cancelled');
    expect(returnMovements($return))->toHaveCount(0);
});

test('a confirmed return can be completed once its credit note exists', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $return = PurchaseReturn::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('completed');
});

test('confirming does not recalculate the frozen rate', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    /** La tasa del día cambia después de emitir: la devolución no la estrena. */
    \App\Modules\ExchangeRate\Models\ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 99.0]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect((float) $return->refresh()->exchange_rate)->toBe(36.5);
});

test('a return already credited cannot be cancelled', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $note = PurchaseCreditNote::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
    ]);

    $return = PurchaseReturn::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'credit_note_id' => $note->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('confirmed');
});

test('a forbidden transition is rejected', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $return = PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('draft');
});

test('a cancelled return is a dead end', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $return = PurchaseReturn::factory()->cancelled()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($return->refresh()->status)->toBe('cancelled');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $return = PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    assignRoleWithPermissions($user, $company, ['purchase-returns.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertForbidden();
});

test('a credit note pointing at the return marks it as credited', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_return_id' => $return->id,
    ]);

    $return->refresh();
    expect($return->credit_note_id)->toBe($note->id);
    expect($return->status)->toBe('completed');
});

test('cancelling the credit note gives the return back to confirmed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_return_id' => $return->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-credit-notes.update-status', ['company' => $company->id, 'id' => $note->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    $return->refresh();
    expect($return->credit_note_id)->toBeNull();
    expect($return->status)->toBe('confirmed');
});

test('a draft return cannot be credited yet', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_return_id' => $return->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_return_id');

    expect($return->refresh()->credit_note_id)->toBeNull();
});

test('a return does not take a second live credit note', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'purchase_return_id' => $return->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_return_id' => $return->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_return_id');
});

test('a return of another company cannot be credited', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseReturnScenario();

    $foreign = PurchaseReturn::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-credit-notes.store', ['company' => $company->id]),
            purchaseCreditNotePayload($supplier, $item, $unit, [
                'purchase_return_id' => $foreign->id,
            ]),
        )
        ->assertSessionHasErrors('purchase_return_id');
});
