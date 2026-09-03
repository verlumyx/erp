<?php

declare(strict_types=1);

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;

test('confirming an adjustment writes the kardex and moves the stock', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    $adjustment->refresh();
    expect($adjustment->status)->toBe('confirmed');
    /** Confirmar es firmar: queda escrito quién y cuándo. */
    expect($adjustment->approved_by)->toBe($user->id);
    expect($adjustment->approved_at)->not->toBeNull();

    $movements = adjustmentMovements($adjustment);
    expect($movements)->toHaveCount(1);
    expect($movements->first()->type)->toBe('adjustment_in');
    expect((float) $movements->first()->quantity)->toBe(2.0);
    expect((float) $movements->first()->unit_cost)->toBe(25.0);

    /** El sobrante entra al promedio vigente: 12 unidades siguen valiendo 25. */
    expect(warehouseBalance($company, $item, $warehouse))->toBe(12.0);
    expect((float) Item::find($item->id)->average_cost)->toBe(25.0);
});

test('a shortage leaves the warehouse with what was actually counted', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'type' => 'loss',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 7,
        ]],
    ]);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    $movements = adjustmentMovements($adjustment);
    expect($movements)->toHaveCount(1);
    expect($movements->first()->type)->toBe('adjustment_out');
    expect((float) $movements->first()->quantity)->toBe(3.0);
    /** La salida se valora al promedio vigente, que no lo cambia. */
    expect((float) $movements->first()->unit_cost)->toBe(25.0);

    expect(warehouseBalance($company, $item, $warehouse))->toBe(7.0);
});

test('a line with lots writes one kardex movement per lot', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    $first = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    $second = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
        'lotId' => $first->id,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 4,
        'unitCost' => 25,
        'lotId' => $second->id,
    ]);

    /** Sobran 2 del primer lote y faltan 3 del segundo. */
    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 13,
            'lots' => [
                ['lot_id' => $first->id, 'counted_quantity' => 12],
                ['lot_id' => $second->id, 'counted_quantity' => 1],
            ],
        ]],
    ]);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    $movements = adjustmentMovements($adjustment)->sortBy('lot_id')->values();
    expect($movements)->toHaveCount(2);

    $byLot = $movements->keyBy('lot_id');

    expect($byLot[$first->id]->type)->toBe('adjustment_in');
    expect((float) $byLot[$first->id]->quantity)->toBe(2.0);

    expect($byLot[$second->id]->type)->toBe('adjustment_out');
    expect((float) $byLot[$second->id]->quantity)->toBe(3.0);

    /** Cada lote queda con lo que se contó de él, no con el neto de la línea. */
    expect(lotBalance($company, $item, $warehouse, $first->id))->toBe(12.0);
    expect(lotBalance($company, $item, $warehouse, $second->id))->toBe(1.0);
});

test('a line that matched the system moves nothing', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 10,
        ]],
    ]);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    expect(adjustmentMovements($adjustment))->toHaveCount(0);
    expect(warehouseBalance($company, $item, $warehouse))->toBe(10.0);
});

test('cancelling a confirmed adjustment puts the stock back', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();
    expect(warehouseBalance($company, $item, $warehouse))->toBe(12.0);

    moveAdjustmentTo($user, $company, $adjustment->refresh(), 'cancelled', 'Se contó mal el pasillo.')
        ->assertSessionHasNoErrors();

    $adjustment->refresh();
    expect($adjustment->status)->toBe('cancelled');
    expect($adjustment->cancelled_at)->not->toBeNull();
    expect($adjustment->cancellation_reason)->toBe('Se contó mal el pasillo.');

    /** Contrapartida emitida: el documento se conserva, el saldo vuelve. */
    expect(adjustmentMovements($adjustment))->toHaveCount(2);
    expect(warehouseBalance($company, $item, $warehouse))->toBe(10.0);
});

test('cancelling a draft reverses nothing because it never moved stock', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    moveAdjustmentTo($user, $company, $adjustment, 'cancelled', 'Se abrió por error.')
        ->assertSessionHasNoErrors();

    expect(Adjustment::find($adjustment->id)->status)->toBe('cancelled');
    expect(adjustmentMovements($adjustment))->toHaveCount(0);
    expect(warehouseBalance($company, $item, $warehouse))->toBe(10.0);
});

test('cancelling always needs a reason', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    moveAdjustmentTo($user, $company, $adjustment, 'cancelled')
        ->assertSessionHasErrors('cancellation_reason');

    expect(Adjustment::find($adjustment->id)->status)->toBe('draft');
});

test('a draft cannot jump straight to confirmed', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    moveAdjustmentTo($user, $company, $adjustment, 'confirmed')
        ->assertSessionHasErrors('status');

    expect(Adjustment::find($adjustment->id)->status)->toBe('draft');
    expect(adjustmentMovements($adjustment))->toHaveCount(0);
});

test('a confirmed adjustment can be closed', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    moveAdjustmentTo($user, $company, $adjustment->refresh(), 'completed')
        ->assertSessionHasNoErrors();

    expect(Adjustment::find($adjustment->id)->status)->toBe('completed');
    /** Cerrarlo no vuelve a mover nada: ya estaba aplicado. */
    expect(adjustmentMovements($adjustment))->toHaveCount(1);
});
