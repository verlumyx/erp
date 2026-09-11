<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

/**
 * Un despacho que solo saca parte de lo pedido deja saldo en el pedido. Ese
 * saldo no se queda sin documento: al confirmar el parcial, el sistema abre por
 * su cuenta otro despacho en borrador con lo que faltó por salir, listo para el
 * siguiente viaje.
 */

/** El despacho en borrador con lo pendiente, distinto del que se acaba de confirmar. */
function remainderDispatchOf(SalesOrder $order, string $exceptId): ?Dispatch
{
    return Dispatch::query()
        ->with('lines')
        ->where('sourceable_type', SalesOrder::MORPH_ALIAS)
        ->where('sourceable_id', $order->id)
        ->where('status', 'draft')
        ->where('id', '!=', $exceptId)
        ->first();
}

test('confirming a partial dispatch opens a draft with what the order still owes', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]],
    ]);
    $orderLine = $order->lines->first();

    /** De las 10 pedidas solo salen 4. */
    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'unit_price' => 100,
            'location_id' => $location->id,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    $remainder = remainderDispatchOf($order, $dispatch->id);

    expect($remainder)->not->toBeNull();
    expect($remainder->status)->toBe('draft');
    expect($remainder->recipient_id)->toBe($client->id);
    expect($remainder->warehouse_id)->toBe($warehouse->id);
    expect($remainder->lines)->toHaveCount(1);
    /** Lo que quedó pendiente: 10 pedidas menos 4 despachadas. */
    expect((float) $remainder->lines->first()->quantity)->toBe(6.0);
    expect($remainder->lines->first()->item_id)->toBe($item->id);
    expect($remainder->lines->first()->sourceable_id)->toBe($orderLine->id);
});

test('a dispatch that empties the order leaves no remainder draft behind', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]],
    ]);
    $orderLine = $order->lines->first();

    /** Salen las 10 pedidas de una vez. */
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

    expect(remainderDispatchOf($order, $dispatch->id))->toBeNull();
});

test('only the line that stayed behind travels to the remainder draft', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    /** Un segundo artículo del pedido, con existencia propia. */
    $second = Item::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $second->id,
        'measurement_unit_id' => $unit->id,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);
    registerInventoryMovement($company, $second, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 100,
            ],
            [
                'item_id' => $second->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 100,
            ],
        ],
    ]);
    $firstLine = $order->lines->firstWhere('item_id', $item->id);

    /** Al primer artículo se le despachan sus 10; al segundo, nada. */
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
            'sourceable_id' => $firstLine->id,
        ]],
    ]);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    $remainder = remainderDispatchOf($order, $dispatch->id);

    expect($remainder)->not->toBeNull();
    /** Solo viaja el artículo que se quedó: el primero ya salió entero. */
    expect($remainder->lines)->toHaveCount(1);
    expect($remainder->lines->first()->item_id)->toBe($second->id);
    expect((float) $remainder->lines->first()->quantity)->toBe(10.0);
});
