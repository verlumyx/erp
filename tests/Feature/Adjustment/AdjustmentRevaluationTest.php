<?php

declare(strict_types=1);

use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Item\Models\Item;
use App\Modules\ItemStock\Models\ItemStock;

use function Pest\Laravel\actingAs;

/** El valor con el que la bodega tiene registrada la existencia del artículo. */
function warehouseValue(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
): float {
    return round((float) ItemStock::query()
        ->where('company_id', $company->id)
        ->where('item_id', $item->id)
        ->where('warehouse_id', $warehouse->id)
        ->sum('total_value'), 2);
}

test('a revaluation reprices the stock without moving quantity', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'type' => 'revaluation',
        'reason' => 'El proveedor corrigió la factura hacia arriba.',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            /** Una revaluación no cuenta: lo contado es lo que dice el sistema. */
            'counted_quantity' => 10,
            'unit_cost' => 30,
        ]],
    ]);

    $line = $adjustment->lines->first();
    expect((float) $line->difference_quantity)->toBe(0.0);
    expect((float) $line->base_quantity)->toBe(0.0);
    expect((float) $line->unit_cost)->toBe(30.0);
    expect($line->movement_type)->toBe(AdjustmentLine::MOVEMENT_IN);
    /** Diez unidades que suben 5 cada una. */
    expect((float) $line->total_cost)->toBe(50.0);
    expect((float) $adjustment->net_cost)->toBe(50.0);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    /**
     * El kardex no sabe escribir un movimiento de cantidad cero, así que la
     * revaluación se expresa como lo que es: sacar al costo viejo y volver a
     * meter al nuevo.
     */
    $movements = adjustmentMovements($adjustment);
    expect($movements)->toHaveCount(2);
    expect($movements->pluck('type')->all())->toBe(['adjustment_out', 'adjustment_in']);
    expect((float) $movements[0]->unit_cost)->toBe(25.0);
    expect((float) $movements[1]->unit_cost)->toBe(30.0);

    /** La cantidad no se movió; el valor sí. */
    expect(warehouseBalance($company, $item, $warehouse))->toBe(10.0);
    expect(warehouseValue($company, $item, $warehouse))->toBe(300.0);
    expect((float) Item::find($item->id)->average_cost)->toBe(30.0);
});

test('a revaluation downwards lowers the value of the inventory', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'type' => 'revaluation',
        'reason' => 'La mercancía se deterioró y vale menos.',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 10,
            'unit_cost' => 20,
        ]],
    ]);

    expect($adjustment->lines->first()->movement_type)->toBe(AdjustmentLine::MOVEMENT_OUT);
    expect((float) $adjustment->net_cost)->toBe(-50.0);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    expect(warehouseBalance($company, $item, $warehouse))->toBe(10.0);
    expect(warehouseValue($company, $item, $warehouse))->toBe(200.0);
});

test('cancelling a revaluation puts the old cost back', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'type' => 'revaluation',
        'reason' => 'Corrección de costo.',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 10,
            'unit_cost' => 30,
        ]],
    ]);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();
    expect(warehouseValue($company, $item, $warehouse))->toBe(300.0);

    moveAdjustmentTo($user, $company, $adjustment->refresh(), 'cancelled', 'El costo nuevo era el equivocado.')
        ->assertSessionHasNoErrors();

    /**
     * Deshacer una revaluación exige el orden inverso: primero se anula la
     * entrada al costo nuevo y después la salida al viejo. Al revés el promedio
     * quedaría a mitad de camino entre los dos.
     */
    expect(warehouseBalance($company, $item, $warehouse))->toBe(10.0);
    expect(warehouseValue($company, $item, $warehouse))->toBe(250.0);
    expect((float) Item::find($item->id)->average_cost)->toBe(25.0);
});

test('a revaluation needs a new cost on every line', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'type' => 'revaluation',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 10,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.unit_cost');
});

test('a revaluation cannot move quantity', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'type' => 'revaluation',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 12,
            'unit_cost' => 30,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.counted_quantity');
});

test('there is nothing to revalue without existence', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'type' => 'revaluation',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 0,
            'unit_cost' => 30,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.item_id');
});
