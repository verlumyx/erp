<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

use function Pest\Laravel\actingAs;

/**
 * El pedido de origen no es un foreign key: es una relación polimórfica cuya
 * integridad valida el Service. Estas pruebas cubren esa validación.
 */
test('a dispatch can come from a sales order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

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
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    /** El tipo guardado es el alias del morph map, no el nombre de la clase. */
    expect($dispatch->sourceable_type)->toBe('sales_order');
    expect($dispatch->sourceable_id)->toBe($order->id);
    expect($dispatch->lines->first()->sourceable_type)->toBe('sales_order_line');
    expect($dispatch->lines->first()->sourceable_id)->toBe($orderLine->id);

    /** Y el pedido lo ve desde el otro lado. */
    expect(SalesOrder::find($order->id)->dispatches)->toHaveCount(1);
});

test('the source order must belong to the same client', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);
    $order = createSalesOrder($user, $company, $other, $warehouse, $item, $unit);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_id');
});

test('a cancelled order cannot be dispatched', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    SalesOrder::where('id', $order->id)->update(['status' => 'cancelled']);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_id');
});

test('a line cannot come from an order the dispatch does not come from', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 100,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.sourceable_id');
});

test('a line source must belong to the order of the header', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $another = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 100,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $another->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.sourceable_id');
});

test('the line unit must be the one of the order line', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    \App\Modules\Item\Models\ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 1,
            'unit_price' => 100,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('more cannot be dispatched than was ordered', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    /** El pedido pide 2. */
    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 3,
            'unit_price' => 100,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.quantity');
});

test('a second dispatch only gets what the first one left', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $source = [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ];

    createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        ...$source,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 100,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        ...$source,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 100,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('dispatches.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.quantity');
});

test('a cancelled dispatch gives its quota back to the order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $lines = [[
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 2,
        'unit_price' => 100,
        'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
        'sourceable_id' => $orderLine->id,
    ]];

    $first = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => $lines,
    ]);

    moveDispatchTo($user, $company, $first, 'cancelled')->assertSessionHasNoErrors();
    expect(Dispatch::find($first->id)->status)->toBe('cancelled');

    /** El cupo vuelve: el segundo despacho puede sacar las mismas 2. */
    $second = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => $lines,
    ]);

    expect($second->lines)->toHaveCount(1);
});
