<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;

use function Pest\Laravel\actingAs;

test('confirming marks what was returned on the invoice line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

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
            'location_id' => $location->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('confirmed');

    /** Confirmar consume el cupo de la factura: es lo que habilita la nota de crédito. */
    expect((float) PurchaseInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(4.0);
});

test('confirming a return writes nothing in the kardex', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    /** La bodega ya tiene existencia antes de la devolución. */
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('confirmed');

    /** La devolución es el acuerdo con el proveedor, no la salida: esa la asienta el despacho. */
    expect(InventoryMovement::query()->where('origin_id', $return->id)->count())->toBe(0);

    /** Y la existencia queda intacta: siguen las 10 unidades que ya estaban. */
    expect(warehouseBalance($company, $item, $warehouse))->toBe(10.0);
});

test('cancelling a confirmed return gives the quota back to the invoice line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

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

    /** Anular libera el cupo: la factura vuelve a poder devolver esas unidades. */
    expect((float) PurchaseInvoiceLine::find($invoiceLine->id)->returned_quantity)->toBe(0.0);
});

test('a draft return can be cancelled', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $return = createPurchaseReturn($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update-status', ['company' => $company->id, 'id' => $return->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasNoErrors();

    expect($return->refresh()->status)->toBe('cancelled');
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
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

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
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

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
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

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
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

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
