<?php

declare(strict_types=1);

use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\Supplier\Models\Supplier;

/**
 * Movimientos del kardex que apuntan a una nota de crédito a proveedor.
 *
 * Ya no hay tipo de origen que filtrar: la nota no escribe en el kardex, así
 * que se busca por el id y lo que debe salir es la nada.
 */
function purchaseCreditNoteMovements(PurchaseCreditNote $note): \Illuminate\Database\Eloquent\Collection
{
    return InventoryMovement::query()
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

test('confirming a note writes nothing in the kardex', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    /** La línea normal ni siquiera nombra una bodega: la nota es solo dinero. */
    $note = createPurchaseCreditNote($user, $company, $supplier, $item, $unit);

    expect($note->lines->first()->warehouse_id)->toBeNull();

    movePurchaseCreditNoteTo($user, $company, $note, 'confirmed')->assertSessionHasNoErrors();

    expect(purchaseCreditNoteMovements($note))->toHaveCount(0);

    /** Y con bodega en la línea tampoco: el dato queda guardado, pero es informativo. */
    $withWarehouse = createPurchaseCreditNote($user, $company, $supplier, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'unit_price' => 25,
        ]],
    ]);

    expect($withWarehouse->lines->first()->warehouse_id)->toBe($warehouse->id);

    movePurchaseCreditNoteTo($user, $company, $withWarehouse, 'confirmed')->assertSessionHasNoErrors();

    expect(purchaseCreditNoteMovements($withWarehouse))->toHaveCount(0);

    /** Solo Ajuste, Entrada y Despacho escriben el kardex: aquí no hay ni un renglón. */
    expect(InventoryMovement::query()->where('company_id', $company->id)->count())->toBe(0);
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
