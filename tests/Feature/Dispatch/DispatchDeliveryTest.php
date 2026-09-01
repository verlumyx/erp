<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

/**
 * Prepara un despacho confirmado con existencia detrás, que es el único estado
 * en el que se puede registrar una entrega.
 *
 * @param  array<string, mixed>  $overrides
 * @return array{0: Dispatch, 1: \App\Modules\Dispatch\Models\DispatchLine}
 */
function confirmedDispatch(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    \App\Modules\WarehouseLocation\Models\WarehouseLocation $location,
    array $overrides = [],
): array {
    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 30]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 100,
            'location_id' => $location->id,
        ]],
        ...$overrides,
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    return [$dispatch->refresh(), $dispatch->lines()->first()];
}

test('a full delivery keeps everything with the client', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'delivered',
        'received_by_name' => 'Ana Pérez',
        'received_by_document' => '12345678',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 10]],
    ])->assertSessionHasNoErrors();

    $dispatch->refresh();
    expect($dispatch->delivery_status)->toBe('delivered');
    expect($dispatch->delivery_date)->not->toBeNull();
    expect($dispatch->received_by_name)->toBe('Ana Pérez');

    $line->refresh();
    expect((float) $line->delivered_quantity)->toBe(10.0);
    expect((float) $line->returned_quantity)->toBe(0.0);

    /** Nada vuelve: el kardex conserva solo la salida. */
    expect(dispatchMovements($dispatch))->toHaveCount(1);
});

test('what the client does not keep comes back into the warehouse', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'partial_delivered',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 6]],
    ])->assertSessionHasNoErrors();

    $dispatch->refresh();
    expect($dispatch->delivery_status)->toBe('partial_delivered');

    $line->refresh();
    expect((float) $line->delivered_quantity)->toBe(6.0);
    /** Sin decir otra cosa, todo lo no entregado se da por devuelto. */
    expect((float) $line->returned_quantity)->toBe(4.0);

    $movements = dispatchMovements($dispatch);
    expect($movements)->toHaveCount(2);

    $entry = $movements->last();
    expect($entry->type)->toBe('in');
    expect((float) $entry->quantity)->toBe(4.0);
    /** Vuelve al costo con el que salió, no al promedio de hoy. */
    expect((float) $entry->unit_cost)->toBe(30.0);
    expect((float) $entry->balance_quantity)->toBe(94.0);
});

test('a rejected delivery brings all the goods back', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'rejected',
        'rejection_reason' => 'El cliente estaba cerrado.',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 0]],
    ])->assertSessionHasNoErrors();

    $dispatch->refresh();
    expect($dispatch->delivery_status)->toBe('rejected');
    expect($dispatch->rejection_reason)->toBe('El cliente estaba cerrado.');

    $entry = dispatchMovements($dispatch)->last();
    expect($entry->type)->toBe('in');
    expect((float) $entry->quantity)->toBe(10.0);
    /** La existencia queda como estaba antes del viaje. */
    expect((float) $entry->balance_quantity)->toBe(100.0);
});

test('a rejection without a reason is refused', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'rejected',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 0]],
    ])->assertSessionHasErrors('rejection_reason');

    expect($dispatch->refresh()->delivery_status)->toBe('in_transit');
});

test('the declared result must match the quantities', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'delivered',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 6]],
    ])->assertSessionHasErrors('delivery_status');

    expect($dispatch->refresh()->delivery_status)->toBe('in_transit');
    expect(dispatchMovements($dispatch))->toHaveCount(1);
});

test('more cannot be delivered than what went out', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'delivered',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 12]],
    ])->assertSessionHasErrors('lines.0.delivered_quantity');
});

test('the client cannot hand back more than what he did not keep', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'partial_delivered',
        'lines' => [[
            'id' => $line->id,
            'delivered_quantity' => 8,
            'returned_quantity' => 5,
        ]],
    ])->assertSessionHasErrors('lines.0.returned_quantity');
});

test('a line left out of the request is taken as fully delivered', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, ['delivery_status' => 'delivered'])
        ->assertSessionHasNoErrors();

    expect((float) $line->refresh()->delivered_quantity)->toBe(10.0);
});

test('a partial delivery gives back to the order what did not stay', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 100, 'unitCost' => 30]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 100],
        ],
    ]);
    $orderLine = $order->lines->first();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 100,
            'location_id' => $location->id,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();
    expect((float) SalesOrderLine::find($orderLine->id)->dispatched_quantity)->toBe(10.0);

    registerDispatchDelivery($user, $company, $dispatch->refresh(), [
        'delivery_status' => 'partial_delivered',
        'lines' => [['id' => $dispatch->lines()->first()->id, 'delivered_quantity' => 4]],
    ])->assertSessionHasNoErrors();

    /** El pedido solo cuenta como despachado lo que se quedó el cliente. */
    $orderLine = SalesOrderLine::find($orderLine->id);
    expect((float) $orderLine->dispatched_quantity)->toBe(4.0);
    expect((float) $orderLine->pending_quantity)->toBe(6.0);
    expect((float) SalesOrder::find($order->id)->dispatched_percent)->toBe(40.0);
});

test('the delivery of a draft cannot be registered', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    registerDispatchDelivery($user, $company, $dispatch)->assertSessionHasErrors('delivery_status');

    expect($dispatch->refresh()->delivery_status)->toBe('pending');
});

test('the delivery is registered only once', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'delivered',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 10]],
    ])->assertSessionHasNoErrors();

    registerDispatchDelivery($user, $company, $dispatch->refresh(), [
        'delivery_status' => 'rejected',
        'rejection_reason' => 'Segundo intento.',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 0]],
    ])->assertSessionHasErrors('delivery_status');

    expect($dispatch->refresh()->delivery_status)->toBe('delivered');
});

test('once the delivery is registered the dispatch can be completed', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'delivered',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 10]],
    ])->assertSessionHasNoErrors();

    moveDispatchTo($user, $company, $dispatch->refresh(), 'completed')->assertSessionHasNoErrors();

    expect($dispatch->refresh()->status)->toBe('completed');
});

test('cancelling after a partial delivery reverses only what is still out', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'partial_delivered',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 6]],
    ])->assertSessionHasNoErrors();

    moveDispatchTo($user, $company, $dispatch->refresh(), 'cancelled')->assertSessionHasNoErrors();

    /** Salida, reingreso y las dos contrapartidas: la existencia queda entera. */
    $movements = dispatchMovements($dispatch);
    expect($movements)->toHaveCount(4);
    expect((float) $movements->last()->balance_quantity)->toBe(100.0);

    expect($dispatch->refresh()->status)->toBe('cancelled');
});

test('registering the delivery requires its own permission', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    [$dispatch, $line] = confirmedDispatch($user, $company, $client, $warehouse, $item, $unit, $location);

    assignRoleWithPermissions($user, $company, ['dispatches.list', 'dispatches.update-status']);

    registerDispatchDelivery($user, $company, $dispatch, [
        'delivery_status' => 'delivered',
        'lines' => [['id' => $line->id, 'delivered_quantity' => 10]],
    ])->assertForbidden();

    expect(Dispatch::find($dispatch->id)->delivery_status)->toBe('in_transit');
});
