<?php

declare(strict_types=1);

use App\Modules\Entry\Models\Entry;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

test('confirming puts the goods into the warehouse at the landed cost', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'freight_amount' => 50,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
            'location_id' => $location->id,
        ]],
    ]);

    /** 50 de flete sobre 250 de mercancía: cada unidad entra a 30. */
    expect((float) $entry->lines->first()->landed_cost)->toBe(30.0);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    expect($entry->refresh()->status)->toBe('confirmed');

    $movements = entryMovements($entry);
    expect($movements)->toHaveCount(1);

    $movement = $movements->first();
    expect($movement->type)->toBe('in');
    expect($movement->warehouse_id)->toBe($warehouse->id);
    expect($movement->location_id)->toBe($location->id);
    expect((float) $movement->quantity)->toBe(10.0);
    expect((float) $movement->unit_cost)->toBe(30.0);
    expect((float) $movement->total_cost)->toBe(300.0);
    expect((float) $movement->balance_quantity)->toBe(10.0);
});

test('a draft moves no stock at all', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    expect(entryMovements($entry))->toHaveCount(0);
    expect(ItemStock::where('item_id', $item->id)->count())->toBe(0);
});

test('what inspection rejected never reaches the kardex', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'inspection_status' => 'partial',
        'inspected_by' => $user->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'rejected_quantity' => 4,
            'rejection_reason' => 'Cuatro cajas golpeadas.',
            'unit_price' => 25,
            'location_id' => $location->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $movement = entryMovements($entry)->first();
    expect((float) $movement->quantity)->toBe(6.0);
    expect((float) $movement->balance_quantity)->toBe(6.0);
});

test('the entry is written in the base unit of the item', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'conversion_factor' => 12,
        'is_base' => 'no',
    ]);

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 2,
            'unit_price' => 120,
            'location_id' => $location->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $movement = entryMovements($entry)->first();
    expect((float) $movement->quantity)->toBe(24.0);
    expect((float) $movement->unit_cost)->toBe(10.0);
});

test('confirming refreshes the average cost of the item', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    expect((float) $item->refresh()->average_cost)->toBe(0.0);

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 20,
            'location_id' => $location->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $item->refresh()->average_cost)->toBe(20.0);

    /** Una segunda entrada más cara pondera el promedio, no lo sustituye. */
    $second = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 40,
            'location_id' => $location->id,
        ]],
    ]);

    moveEntryTo($user, $company, $second, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $item->refresh()->average_cost)->toBe(30.0);
});

test('the lot of the supplier is created when the entry is confirmed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
            'location_id' => $location->id,
            'lot_number' => 'L-2026-04',
            'expires_at' => now()->addYear()->toDateString(),
        ]],
    ]);

    /** En borrador el lote todavía no existe: solo el número del papel. */
    expect($entry->lines->first()->lot_id)->toBeNull();
    expect(ItemLot::count())->toBe(0);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $lot = ItemLot::where('lot_number', 'L-2026-04')->first();
    expect($lot)->not->toBeNull();
    expect($lot->item_id)->toBe($item->id);
    expect($lot->supplier_id)->toBe($supplier->id);
    expect($lot->expires_at?->toDateString())->toBe(now()->addYear()->toDateString());

    /** Y la línea queda apuntando al lote, no al papel. */
    expect($entry->lines()->first()->lot_id)->toBe($lot->id);
    expect(entryMovements($entry)->first()->lot_id)->toBe($lot->id);
});

test('a lot that already exists is reused instead of duplicated', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $existing = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'lot_number' => 'L-77',
        'created_by' => $user->id,
    ]);

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'unit_price' => 25,
            'location_id' => $location->id,
            'lot_number' => 'L-77',
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    expect(ItemLot::where('item_id', $item->id)->count())->toBe(1);
    expect($entry->lines()->first()->lot_id)->toBe($existing->id);
});

test('a serialized item enters unit by unit and registers its serials', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    Item::where('id', $item->id)->update(['type' => 'serialized']);

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 3,
            'unit_price' => 100,
            'location_id' => $location->id,
            'serial_numbers' => ['S-1', 'S-2', 'S-3'],
        ]],
    ]);

    expect(ItemSerial::count())->toBe(0);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $serials = ItemSerial::where('item_id', $item->id)->orderBy('serial_number')->get();
    expect($serials)->toHaveCount(3);
    expect($serials->first()->warehouse_id)->toBe($warehouse->id);
    expect($serials->first()->status)->toBe('available');

    /** Un asiento por serie: el kardex identifica la unidad por la suya. */
    $movements = entryMovements($entry);
    expect($movements)->toHaveCount(3);
    expect($movements->pluck('quantity')->map(fn ($q): float => (float) $q)->all())->toBe([1.0, 1.0, 1.0]);
    expect($movements->pluck('serial_id')->filter()->unique())->toHaveCount(3);
});

test('cancelling a confirmed entry takes the goods back out with a counterpart', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

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
    moveEntryTo($user, $company, $entry, 'cancelled')->assertSessionHasNoErrors();

    expect($entry->refresh()->status)->toBe('cancelled');
    expect($entry->cancelled_at)->not->toBeNull();

    $movements = InventoryMovement::query()
        ->where('origin_type', Entry::MOVEMENT_ORIGIN_TYPE)
        ->where('origin_id', $entry->id)
        ->orderBy('created_at')
        ->get();

    /** El asiento original se conserva y recibe su contrapartida. */
    expect($movements)->toHaveCount(2);
    expect($movements->last()->type)->toBe('out');
    expect($movements->last()->reversal_of_id)->toBe($movements->first()->id);

    $stock = ItemStock::where('item_id', $item->id)->where('location_id', $location->id)->first();
    expect((float) $stock->quantity)->toBe(0.0);
});

test('cancelling a draft reverses nothing', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    moveEntryTo($user, $company, $entry, 'cancelled')->assertSessionHasNoErrors();

    expect($entry->refresh()->status)->toBe('cancelled');
    expect(entryMovements($entry))->toHaveCount(0);
});

test('the status only moves along the path the document allows', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    /** De borrador no se salta a cerrada. */
    moveEntryTo($user, $company, $entry, 'completed')->assertSessionHasErrors('status');
    expect($entry->refresh()->status)->toBe('draft');

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();
    moveEntryTo($user, $company, $entry, 'completed')->assertSessionHasNoErrors();

    /** Y cerrada es terminal. */
    moveEntryTo($user, $company, $entry, 'cancelled')->assertSessionHasErrors('status');
    expect($entry->refresh()->status)->toBe('completed');
});

test('confirming without a location and without a default one is refused', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    WarehouseLocation::where('id', $location->id)->update(['is_default' => 'no']);

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasErrors('status');

    expect($entry->refresh()->status)->toBe('draft');
    expect(entryMovements($entry))->toHaveCount(0);
});
