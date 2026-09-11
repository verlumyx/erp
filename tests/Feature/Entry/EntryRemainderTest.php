<?php

declare(strict_types=1);

use App\Modules\Entry\Models\Entry;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

/**
 * Una entrada que solo recibe parte de lo pedido deja saldo en la orden de
 * compra. Ese saldo no se queda sin documento: al confirmar el parcial, el
 * sistema abre por su cuenta otra entrada en borrador con lo que faltó por
 * llegar, lista para la próxima recepción.
 */

/** La entrada en borrador con lo pendiente, distinta de la que se acaba de confirmar. */
function remainderEntryOf(PurchaseOrder $order, string $exceptId): ?Entry
{
    return Entry::query()
        ->with('lines')
        ->where('sourceable_type', PurchaseOrder::MORPH_ALIAS)
        ->where('sourceable_id', $order->id)
        ->where('status', 'draft')
        ->where('id', '!=', $exceptId)
        ->first();
}

test('confirming a partial entry opens a draft with what the order still awaits', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);
    $orderLine = $order->lines->first();

    /** De las 10 pedidas solo llegan 4. */
    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $remainder = remainderEntryOf($order, $entry->id);

    expect($remainder)->not->toBeNull();
    expect($remainder->status)->toBe('draft');
    expect($remainder->warehouse_id)->toBe($warehouse->id);
    expect($remainder->lines)->toHaveCount(1);
    /** Lo que quedó pendiente: 10 pedidas menos 4 recibidas. */
    expect((float) $remainder->lines->first()->quantity)->toBe(6.0);
    expect((float) $remainder->lines->first()->received_quantity)->toBe(6.0);
    expect($remainder->lines->first()->item_id)->toBe($item->id);
    expect($remainder->lines->first()->sourceable_id)->toBe($orderLine->id);
});

test('an entry that completes the order leaves no remainder draft behind', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);
    $orderLine = $order->lines->first();

    /** Llegan las 10 pedidas de una vez. */
    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    expect(remainderEntryOf($order, $entry->id))->toBeNull();
});
