<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\Supplier\Models\Supplier;

/** Movimientos del kardex que escribió una nota de crédito a proveedor. */
function purchaseCreditNoteMovements(PurchaseCreditNote $note): \Illuminate\Database\Eloquent\Collection
{
    return InventoryMovement::query()
        ->where('origin_type', PurchaseCreditNote::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $note->id)
        ->orderBy('created_at')
        ->get();
}


test('confirming lowers what is owed to the supplier', function () {
    [$user, $company, , , $item, $unit] = purchaseReturnScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 500]);

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 25,
        ]],
    ]);

    expect((float) $note->total)->toBe(50.0);

    /** En borrador la deuda sigue entera. */
    expect((float) $supplier->refresh()->current_balance)->toBe(500.0);

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $supplier->refresh()->current_balance)->toBe(450.0);
});

test('a note that does not affect inventory moves no stock', function () {
    [$user, $company, $supplier, , $item, $unit] = purchaseReturnScenario();

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit);

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    expect(purchaseCreditNoteMovements($note))->toHaveCount(0);
});

test('a note that affects inventory takes the goods out of the warehouse', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    /** La mercancía tiene que estar en la bodega antes de poder devolverla. */
    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
            'location_id' => $location->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'affects_inventory' => 'yes',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    $movements = purchaseCreditNoteMovements($note);
    expect($movements)->toHaveCount(1);

    $movement = $movements->first();
    expect($movement->type)->toBe('out');
    expect($movement->warehouse_id)->toBe($warehouse->id);
    expect((float) $movement->quantity)->toBe(2.0);
    expect((float) $movement->balance_quantity)->toBe(8.0);
});

test('cancelling a confirmed note gives the payable back', function () {
    [$user, $company, , , $item, $unit] = purchaseReturnScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 500]);

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit);

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();
    expect((float) $supplier->refresh()->current_balance)->toBe(450.0);

    movePurchaseCreditNoteTo($user, $company, $note, 'cancelled')->assertSessionHasNoErrors();

    expect($note->refresh()->status)->toBe('cancelled');
    expect((float) $supplier->refresh()->current_balance)->toBe(500.0);
});

test('cancelling a draft moves no balance at all', function () {
    [$user, $company, , , $item, $unit] = purchaseReturnScenario();

    $supplier = Supplier::factory()->create(['company_id' => $company->id, 'current_balance' => 500]);

    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit);

    movePurchaseCreditNoteTo($user, $company, $note, 'cancelled')->assertSessionHasNoErrors();

    expect((float) $supplier->refresh()->current_balance)->toBe(500.0);
});
