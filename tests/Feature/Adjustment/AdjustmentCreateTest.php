<?php

declare(strict_types=1);

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('an adjustment can be created', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    /** La bodega tiene 10 unidades compradas a 25. */
    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'count_id' => 'CONTEO-07',
        'notes' => 'Se recontó el pasillo tres.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('adjustments.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $adjustment = Adjustment::with('lines')->find($payload['id']);
    expect($adjustment)->not->toBeNull();
    expect($adjustment->code)->toBe('AJU000001');
    expect($adjustment->status)->toBe('draft');
    expect($adjustment->company_id)->toBe($company->id);
    expect($adjustment->created_by)->toBe($user->id);
    expect($adjustment->warehouse_id)->toBe($warehouse->id);
    expect($adjustment->type)->toBe('physical_count');
    expect($adjustment->count_id)->toBe('CONTEO-07');
    /** Nace sin firmar: la existencia todavía no ha cambiado. */
    expect($adjustment->approved_by)->toBeNull();
    expect($adjustment->lines)->toHaveCount(1);

    $line = $adjustment->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    /** El sistema decía 10 y se contaron 12: sobran 2. */
    expect((float) $line->system_quantity)->toBe(10.0);
    expect((float) $line->counted_quantity)->toBe(12.0);
    expect((float) $line->difference_quantity)->toBe(2.0);
    expect((float) $line->base_quantity)->toBe(2.0);
    expect($line->movement_type)->toBe(AdjustmentLine::MOVEMENT_IN);
    expect((float) $line->unit_cost)->toBe(25.0);
    expect((float) $line->total_cost)->toBe(50.0);

    expect((float) $adjustment->total_quantity_in)->toBe(2.0);
    expect((float) $adjustment->total_quantity_out)->toBe(0.0);
    expect((float) $adjustment->net_cost)->toBe(50.0);
});

test('the system quantity comes from the stock and ignores what the client sends', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 12,
            /** Mentiras del cliente: el backend las descarta. */
            'system_quantity' => 999,
            'difference_quantity' => 999,
            'unit_cost' => 999,
            'total_cost' => 999,
        ]],
    ]);

    $line = $adjustment->lines->first();
    expect((float) $line->system_quantity)->toBe(10.0);
    expect((float) $line->difference_quantity)->toBe(2.0);
    expect((float) $line->unit_cost)->toBe(25.0);
    expect((float) $line->total_cost)->toBe(50.0);
});

test('counting less than the system says leaves a shortage', function () {
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

    $line = $adjustment->lines->first();
    expect((float) $line->difference_quantity)->toBe(-3.0);
    expect($line->movement_type)->toBe(AdjustmentLine::MOVEMENT_OUT);
    expect((float) $line->total_cost)->toBe(75.0);

    expect((float) $adjustment->total_quantity_out)->toBe(3.0);
    expect((float) $adjustment->total_cost_out)->toBe(75.0);
    /** El faltante baja el valor del inventario. */
    expect((float) $adjustment->net_cost)->toBe(-75.0);
});

test('the difference is expressed in base units when the line counts in another unit', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'conversion_factor' => 12,
        'is_base' => 'no',
    ]);

    /** 60 unidades base son 5 cajas de 12. */
    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 60,
        'unitCost' => 10,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'counted_quantity' => 6,
        ]],
    ]);

    $line = $adjustment->lines->first();
    expect((float) $line->system_quantity)->toBe(5.0);
    expect((float) $line->counted_quantity)->toBe(6.0);
    expect((float) $line->difference_quantity)->toBe(1.0);
    /** Una caja de más son doce unidades base. */
    expect((float) $line->base_quantity)->toBe(12.0);
    expect((float) $line->total_cost)->toBe(120.0);
});

test('an adjustment needs a reason', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $payload = adjustmentPayload($warehouse, $item, $unit, ['reason' => '']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('reason');

    expect(Adjustment::count())->toBe(0);
});

test('an adjustment needs at least one active line', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 1,
            'status' => 'inactive',
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines');

    expect(Adjustment::count())->toBe(0);
});

test('an increase-only adjustment rejects a line that leaves a shortage', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'direction' => 'in',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 4,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.counted_quantity');

    expect(Adjustment::count())->toBe(0);
});

test('two lines cannot count the same existence', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'counted_quantity' => 3,
            ],
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'counted_quantity' => 5,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.1.item_id');
});

test('the line location has to belong to the warehouse of the adjustment', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $other = Warehouse::factory()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $other->id,
        'created_by' => $user->id,
    ]);

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 1,
            'location_id' => $location->id,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.location_id');
});

test('a line cannot carry a lot for an item that does not track lots', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
    ]);
    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $lot = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $service->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 1,
            'lots' => [['lot_id' => $lot->id, 'counted_quantity' => 1]],
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.lots');
});

test('the lots of a line have to add up to what the line counted', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    $lot = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
        'lotId' => $lot->id,
    ]);

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 12,
            /** Se contaron 12 pero solo se repartieron 8. */
            'lots' => [['lot_id' => $lot->id, 'counted_quantity' => 8]],
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.lots');
});

test('the same lot cannot be counted twice in one line', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $lot = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 12,
            'lots' => [
                ['lot_id' => $lot->id, 'counted_quantity' => 6],
                ['lot_id' => $lot->id, 'counted_quantity' => 6],
            ],
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.lots');
});

test('a line counts each of its lots against its own stock', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    $first = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    $second = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    /** Diez unidades en el primer lote y cuatro en el segundo. */
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

    /** Se cuentan 12 del primero —sobran 2— y 1 del segundo —faltan 3—. */
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

    $line = $adjustment->lines->first();
    /** La línea es la suma de sus lotes: 14 en el sistema, 13 contadas. */
    expect((float) $line->system_quantity)->toBe(14.0);
    expect((float) $line->difference_quantity)->toBe(-1.0);

    $lots = $line->lots()->orderBy('line_number')->get();
    expect($lots)->toHaveCount(2);

    expect((float) $lots[0]->system_quantity)->toBe(10.0);
    expect((float) $lots[0]->difference_quantity)->toBe(2.0);
    expect($lots[0]->movement_type)->toBe(AdjustmentLine::MOVEMENT_IN);

    expect((float) $lots[1]->system_quantity)->toBe(4.0);
    expect((float) $lots[1]->difference_quantity)->toBe(-3.0);
    expect($lots[1]->movement_type)->toBe(AdjustmentLine::MOVEMENT_OUT);

    /** Los totales separan los dos lados, aunque vengan de la misma línea. */
    expect((float) $adjustment->total_quantity_in)->toBe(2.0);
    expect((float) $adjustment->total_quantity_out)->toBe(3.0);
    expect((float) $adjustment->net_cost)->toBe(-25.0);
});

test('a serialized line names the units it counted', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    Item::where('id', $item->id)->update(['type' => ItemSerial::TRACKABLE_ITEM_TYPE]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 3,
        'unitCost' => 25,
    ]);

    $serials = ItemSerial::factory()->count(3)->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 3,
            'serials' => $serials->map(fn ($serial): array => ['serial_id' => $serial->id])->all(),
        ]],
    ]);

    $line = $adjustment->lines->first();
    expect((float) $line->difference_quantity)->toBe(0.0);
    expect($line->serials()->where('status', 'active')->count())->toBe(3);
});

test('a serialized line can name the unit that is missing', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    Item::where('id', $item->id)->update(['type' => ItemSerial::TRACKABLE_ITEM_TYPE]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 1,
        'unitCost' => 25,
    ]);

    $serial = ItemSerial::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    /** No apareció: se cuenta cero y se nombra la unidad que falta. */
    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'type' => 'loss',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 0,
            'serials' => [['serial_id' => $serial->id]],
        ]],
    ]);

    $line = $adjustment->lines->first();
    expect((float) $line->difference_quantity)->toBe(-1.0);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    /** El kardex nombra la unidad: no hay ambigüedad, sale una y hay una. */
    $movement = adjustmentMovements($adjustment)->first();
    expect($movement->type)->toBe('adjustment_out');
    expect($movement->serial_id)->toBe($serial->id);
});

test('a serialized line cannot name a number of units that means nothing', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    Item::where('id', $item->id)->update(['type' => ItemSerial::TRACKABLE_ITEM_TYPE]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 3,
        'unitCost' => 25,
    ]);

    $serials = ItemSerial::factory()->count(2)->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    /** Se contaron 3 y falta 0, pero se nombran 2 unidades: no dice nada. */
    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 3,
            'serials' => $serials->map(fn ($serial): array => ['serial_id' => $serial->id])->all(),
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('adjustments.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.serials');
});

test('the create screen renders with its catalogs', function () {
    [$user, $company] = adjustmentScenario();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('adjustments.create', ['company' => $company->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('adjustments/create')
            ->has('options.warehouses')
            ->has('options.locations')
            ->has('options.counters')
            ->has('options.approval_threshold')
        );
});
