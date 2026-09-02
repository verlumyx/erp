<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

test('confirming a purchase order announces the goods as incoming', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    /** En borrador no hay nada anunciado. */
    expect(stockAt($item, $location))->toBeNull();

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $stock = stockAt($item, $location);
    expect($stock)->not->toBeNull();
    /** Confirmar no mete mercancía: la anuncia. */
    expect((float) $stock->quantity)->toBe(0.0);
    expect((float) $stock->incoming_quantity)->toBe(10.0);
});

test('cancelling a confirmed order wipes what it had announced', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();
    expect((float) stockAt($item, $location)->incoming_quantity)->toBe(10.0);

    movePurchaseOrderTo($user, $company, $order, 'cancelled', [
        'cancellation_reason' => 'El proveedor no pudo servirla.',
    ])->assertSessionHasNoErrors();

    expect((float) stockAt($item, $location)->incoming_quantity)->toBe(0.0);
});

test('receiving the goods consumes what was in transit', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $orderLine = $order->refresh()->load('lines')->lines->first();

    /** Llega la mitad del pedido. */
    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
            'quantity' => 6,
            'unit_price' => 25,
            'location_id' => $location->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $stock = stockAt($item, $location);
    expect((float) $stock->quantity)->toBe(6.0);
    /** Lo que ya llegó dejó de estar en camino; siguen esperándose 4. */
    expect((float) $stock->incoming_quantity)->toBe(4.0);
});

test('cancelling the entry puts the goods back in transit', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $orderLine = $order->refresh()->load('lines')->lines->first();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
            'quantity' => 6,
            'unit_price' => 25,
            'location_id' => $location->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();
    moveEntryTo($user, $company, $entry, 'cancelled')->assertSessionHasNoErrors();

    $stock = stockAt($item, $location);
    expect((float) $stock->quantity)->toBe(0.0);
    expect((float) $stock->incoming_quantity)->toBe(10.0);
});
