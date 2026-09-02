<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

use function Pest\Laravel\actingAs;

/** El despacho que el pedido generó, si generó alguno. */
function generatedDispatch(SalesOrder $order): ?Dispatch
{
    return Dispatch::query()
        ->with('lines')
        ->where('sourceable_type', SalesOrder::MORPH_ALIAS)
        ->where('sourceable_id', $order->id)
        ->first();
}

/**
 * Carga las series que faltaban en la línea del borrador generado, por la misma
 * vía por la que las cargaría quien arma la carga: la pantalla del despacho.
 *
 * @param  array<int, string>  $serialIds
 */
function loadDispatchSerials(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    Dispatch $dispatch,
    array $serialIds,
): void {
    $line = $dispatch->lines->first();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('dispatches.update', ['company' => $company->id, 'id' => $dispatch->id]), [
            'client_id' => $dispatch->recipient_id,
            'sourceable_type' => $dispatch->sourceable_type,
            'sourceable_id' => $dispatch->sourceable_id,
            'warehouse_id' => $dispatch->warehouse_id,
            'dispatch_date' => $dispatch->dispatch_date->toDateString(),
            'lines' => [[
                'id' => $line->id,
                'item_id' => $line->item_id,
                'measurement_unit_id' => $line->measurement_unit_id,
                'quantity' => (float) $line->quantity,
                'sourceable_type' => $line->sourceable_type,
                'sourceable_id' => $line->sourceable_id,
                'serials' => array_map(
                    static fn (string $id): array => ['serial_id' => $id],
                    $serialIds,
                ),
            ]],
        ])
        ->assertSessionHasNoErrors();

    $dispatch->refresh()->load('lines.serials');
}

test('approving a sales order writes the dispatch that will take the goods out', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    /** En borrador el pedido no ha generado nada. */
    expect(generatedDispatch($order))->toBeNull();

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = generatedDispatch($order);

    expect($dispatch)->not->toBeNull();
    /** Nace en borrador: aprobar el pedido no saca nada de la bodega. */
    expect($dispatch->status)->toBe('draft');
    expect($dispatch->delivery_status)->toBe('pending');
    expect($dispatch->recipient_type)->toBe('client');
    expect($dispatch->recipient_id)->toBe($client->id);
    expect($dispatch->warehouse_id)->toBe($warehouse->id);

    expect($dispatch->lines)->toHaveCount(1);

    $line = $dispatch->lines->first();
    expect($line->item_id)->toBe($item->id);
    expect($line->measurement_unit_id)->toBe($unit->id);
    expect((float) $line->quantity)->toBe(2.0);
    /** La línea queda colgada de la línea del pedido que la originó. */
    expect($line->sourceable_type)->toBe(SalesOrderLine::MORPH_ALIAS);
    expect($line->sourceable_id)->toBe($order->lines->first()->id);
});

test('the generated dispatch inherits the delivery address and the route of the order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $address = \App\Modules\Client\Models\ClientAddress::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'client_address_id' => $address->id,
    ]);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    expect(generatedDispatch($order)->client_address_id)->toBe($address->id);
});

test('the price of the generated line comes from the order, not from zero', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 3,
            'unit_price' => 80,
        ]],
    ]);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $line = generatedDispatch($order)->lines->first();

    expect((float) $line->unit_price)->toBe(80.0);
    expect((float) $line->subtotal)->toBe(240.0);
});

test('a line of an item that carries no stock never reaches the dispatch', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
    ]);

    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => 100,
            ],
            [
                'item_id' => $service->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 250,
            ],
        ],
    ]);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = generatedDispatch($order);

    /** La instalación que se vende con el equipo no viaja en el camión. */
    expect($dispatch->lines)->toHaveCount(1);
    expect($dispatch->lines->first()->item_id)->toBe($item->id);
});

test('an order with nothing to dispatch generates no dispatch at all', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesOrderScenario();

    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
    ]);

    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $service, $unit, [
        'lines' => [[
            'item_id' => $service->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 250,
        ]],
    ]);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    expect(generatedDispatch($order))->toBeNull();
});

test('the generated dispatch moves no stock and the order stays reserved', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $location = stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = generatedDispatch($order);

    expect(dispatchMovements($dispatch))->toHaveCount(0);

    /** El borrador no libera la reserva: eso lo hace confirmar el despacho. */
    $stock = stockAt($item, $location);
    expect((float) $stock->quantity)->toBe(100.0);
    expect((float) $stock->reserved_quantity)->toBe(2.0);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    expect(dispatchMovements($dispatch))->toHaveCount(1);

    $stock = stockAt($item, $location);
    expect((float) $stock->quantity)->toBe(98.0);
    expect((float) $stock->reserved_quantity)->toBe(0.0);
});

test('a serialized item travels without serials and the dispatch demands them when confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    Item::where('id', $item->id)->update(['type' => 'serialized']);

    $serials = ItemSerial::factory()->count(2)->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'available',
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = generatedDispatch($order);

    /** El borrador trae la cantidad, no las series: nadie ha tocado la carga. */
    expect((float) $dispatch->lines->first()->quantity)->toBe(2.0);
    expect($dispatch->lines->first()->serials)->toHaveCount(0);

    /** Y sin ellas la mercancía no se mueve. */
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasErrors('status');
    expect($dispatch->refresh()->status)->toBe('draft');
    expect(dispatchMovements($dispatch))->toHaveCount(0);

    loadDispatchSerials($user, $company, $dispatch, $serials->pluck('id')->all());

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();
    expect(dispatchMovements($dispatch))->toHaveCount(2);
});

test('cancelling the order cancels the dispatch it had generated', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = generatedDispatch($order);

    moveSalesOrderTo($user, $company, $order, 'cancelled', [
        'cancellation_reason' => 'El cliente desistió del pedido.',
    ])->assertSessionHasNoErrors();

    expect($dispatch->refresh()->status)->toBe('cancelled');
});

test('an order whose dispatch is already confirmed cannot be cancelled', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $location = stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = generatedDispatch($order);
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    /** La mercancía ya salió: el pedido no se anula hasta anular el despacho. */
    moveSalesOrderTo($user, $company, $order, 'cancelled', [
        'cancellation_reason' => 'El cliente desistió del pedido.',
    ])->assertSessionHasErrors('status');

    expect($order->refresh()->status)->toBe('confirmed');
    /** Y la existencia no se tocó al intentarlo. */
    expect((float) stockAt($item, $location)->quantity)->toBe(98.0);

    /** Anulado el despacho, el pedido sí se anula. */
    moveDispatchTo($user, $company, $dispatch, 'cancelled')->assertSessionHasNoErrors();

    moveSalesOrderTo($user, $company, $order, 'cancelled', [
        'cancellation_reason' => 'El cliente desistió del pedido.',
    ])->assertSessionHasNoErrors();

    expect($order->refresh()->status)->toBe('cancelled');
});

test('an order that already had its dispatch made by hand does not get a second one', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $manual = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    moveSalesOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $dispatches = Dispatch::where('sourceable_id', $order->id)->get();

    expect($dispatches)->toHaveCount(1);
    expect($dispatches->first()->id)->toBe($manual->id);
});
