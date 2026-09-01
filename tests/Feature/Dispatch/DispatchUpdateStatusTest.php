<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Item\Models\Item;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

test('confirming takes the goods out of the warehouse at the current average cost', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    /** La bodega tiene existencia comprada a 20 y a 40: promedio 30. */
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 20]);
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 40]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 100,
            'location_id' => $location->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    $dispatch->refresh();
    expect($dispatch->status)->toBe('confirmed');
    /** Confirmar pone la mercancía en la calle. */
    expect($dispatch->delivery_status)->toBe('in_transit');

    $movements = dispatchMovements($dispatch);
    expect($movements)->toHaveCount(1);

    $movement = $movements->first();
    expect($movement->type)->toBe('out');
    expect($movement->warehouse_id)->toBe($warehouse->id);
    expect($movement->location_id)->toBe($location->id);
    expect((float) $movement->quantity)->toBe(2.0);
    /** La salida se valora al promedio vigente. */
    expect((float) $movement->unit_cost)->toBe(30.0);
    expect((float) $movement->balance_quantity)->toBe(18.0);

    /** Y el costo real se copia a la línea y a la cabecera. */
    expect((float) $dispatch->lines()->first()->unit_cost)->toBe(30.0);
    expect((float) $dispatch->total_cost)->toBe(60.0);
});

test('the exit is written in the base unit of the item', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    $box = \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()->create(['company_id' => $company->id]);
    \App\Modules\Item\Models\ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 5]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 2,
            'unit_price' => 60,
            'location_id' => $location->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    /** 2 cajas de 12 son 24 unidades base. */
    expect((float) dispatchMovements($dispatch)->first()->quantity)->toBe(24.0);
});

test('a line without location falls back to the default one of the warehouse', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    expect(dispatchMovements($dispatch)->first()->location_id)->toBe($location->id);
});

test('a warehouse without a default location cannot confirm the dispatch', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    WarehouseLocation::where('id', $location->id)->update(['is_default' => 'no']);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasErrors('status');

    expect($dispatch->refresh()->status)->toBe('draft');
    expect(dispatchMovements($dispatch))->toHaveCount(0);
});

test('a dispatch without enough stock is not confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 1, 'unitCost' => 5]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 5,
            'unit_price' => 100,
            'location_id' => $location->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasErrors('status');

    expect($dispatch->refresh()->status)->toBe('draft');
    expect(dispatchMovements($dispatch))->toHaveCount(0);
});

test('a service item does not reach the kardex', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    Item::where('id', $item->id)->update(['type' => 'service']);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    expect($dispatch->refresh()->status)->toBe('confirmed');
    expect(dispatchMovements($dispatch))->toHaveCount(0);
});

test('confirming moves the order forward and releases its reservation', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();
    SalesOrderLine::where('id', $orderLine->id)->update(['reserved_quantity' => 2]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 100,
            'location_id' => $location->id,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    $orderLine = SalesOrderLine::find($orderLine->id);
    expect((float) $orderLine->dispatched_quantity)->toBe(2.0);
    expect((float) $orderLine->pending_quantity)->toBe(0.0);
    /** Lo que salió de la bodega ya no puede seguir comprometido. */
    expect((float) $orderLine->reserved_quantity)->toBe(0.0);

    expect((float) SalesOrder::find($order->id)->dispatched_percent)->toBe(100.0);
});

test('cancelling a confirmed dispatch brings the goods back with a counter-entry', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 100,
            'location_id' => $location->id,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();
    moveDispatchTo($user, $company, $dispatch, 'cancelled')->assertSessionHasNoErrors();

    $dispatch->refresh();
    expect($dispatch->status)->toBe('cancelled');
    expect($dispatch->cancelled_at)->not->toBeNull();

    /** La salida sigue escrita y su contrapartida al lado: nada se borra. */
    $movements = dispatchMovements($dispatch);
    expect($movements)->toHaveCount(2);
    expect($movements->first()->status)->toBe('reversed');
    expect($movements->last()->type)->toBe('in');
    expect($movements->last()->reversal_of_id)->toBe($movements->first()->id);

    /** La existencia vuelve a estar entera. */
    expect((float) $movements->last()->balance_quantity)->toBe(10.0);

    /** Y el pedido recupera lo despachado. */
    expect((float) SalesOrderLine::find($orderLine->id)->dispatched_quantity)->toBe(0.0);
});

test('cancelling a draft reverses nothing', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    moveDispatchTo($user, $company, $dispatch, 'cancelled')->assertSessionHasNoErrors();

    expect($dispatch->refresh()->status)->toBe('cancelled');
    expect(dispatchMovements($dispatch))->toHaveCount(0);
});

test('a dispatch cannot be completed before its delivery is registered', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();
    moveDispatchTo($user, $company, $dispatch, 'completed')->assertSessionHasErrors('status');

    expect($dispatch->refresh()->status)->toBe('confirmed');
});

test('a dispatch cannot jump from draft to completed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    moveDispatchTo($user, $company, $dispatch, 'completed')->assertSessionHasErrors('status');

    expect($dispatch->refresh()->status)->toBe('draft');
});

test('a cancelled dispatch is final', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    moveDispatchTo($user, $company, $dispatch, 'cancelled')->assertSessionHasNoErrors();
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasErrors('status');

    expect($dispatch->refresh()->status)->toBe('cancelled');
});

test('changing the status requires its own permission', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['dispatches.list', 'dispatches.show']);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertForbidden();

    expect(Dispatch::find($dispatch->id)->status)->toBe('draft');
});
