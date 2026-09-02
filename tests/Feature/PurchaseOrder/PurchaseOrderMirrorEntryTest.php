<?php

declare(strict_types=1);

use App\Modules\Entry\Models\Entry;
use App\Modules\Item\Models\Item;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

/**
 * Carga las series que faltaban en la línea del borrador generado, por la misma
 * vía por la que las cargaría bodega: la pantalla de la entrada.
 *
 * @param  array<int, string>  $serials
 */
function loadEntrySerials(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    Entry $entry,
    array $serials,
): void {
    $line = $entry->lines->first();

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('entries.update', ['company' => $company->id, 'id' => $entry->id]), [
            'supplier_id' => $entry->supplier_id,
            'sourceable_type' => $entry->sourceable_type,
            'sourceable_id' => $entry->sourceable_id,
            'warehouse_id' => $entry->warehouse_id,
            'entry_date' => $entry->entry_date->toDateString(),
            'entry_type' => $entry->entry_type,
            'inspection_status' => $entry->inspection_status,
            'currency' => $entry->currency,
            'lines' => [[
                'id' => $line->id,
                'item_id' => $line->item_id,
                'measurement_unit_id' => $line->measurement_unit_id,
                'quantity' => (float) $line->quantity,
                'sourceable_type' => $line->sourceable_type,
                'sourceable_id' => $line->sourceable_id,
                'serials' => array_map(
                    static fn (string $serial): array => ['serial_number' => $serial],
                    $serials,
                ),
            ]],
        ])
        ->assertSessionHasNoErrors();

    $entry->refresh()->load('lines.serials');
}

/** La entrada que la orden generó, si generó alguna. */
function generatedEntry(PurchaseOrder $order): ?Entry
{
    return Entry::query()
        ->with('lines')
        ->where('sourceable_type', PurchaseOrder::MORPH_ALIAS)
        ->where('sourceable_id', $order->id)
        ->first();
}

test('approving a purchase order writes the entry that will receive the goods', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    /** En borrador la orden no ha generado nada. */
    expect(generatedEntry($order))->toBeNull();

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $entry = generatedEntry($order);

    expect($entry)->not->toBeNull();
    /** Nace en borrador: aprobar la orden no mete nada en la bodega. */
    expect($entry->status)->toBe('draft');
    expect($entry->entry_type)->toBe('purchase');
    expect($entry->supplier_id)->toBe($supplier->id);
    expect($entry->warehouse_id)->toBe($warehouse->id);
    expect($entry->currency)->toBe($order->currency);

    expect($entry->lines)->toHaveCount(1);

    $line = $entry->lines->first();
    expect($line->item_id)->toBe($item->id);
    expect($line->measurement_unit_id)->toBe($unit->id);
    expect((float) $line->quantity)->toBe(10.0);
    expect((float) $line->rejected_quantity)->toBe(0.0);
    /** La línea queda colgada de la línea de la orden que la originó. */
    expect($line->sourceable_type)->toBe(PurchaseOrderLine::MORPH_ALIAS);
    expect($line->sourceable_id)->toBe($order->lines->first()->id);
});

test('the cost of the generated line comes from the order, not from zero', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'unit_price' => 37.5,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $line = generatedEntry($order)->lines->first();

    expect((float) $line->unit_price)->toBe(37.5);
    expect((float) $line->landed_cost)->toBe(37.5);
});

test('a line of an item that carries no stock never reaches the entry', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 25,
            ],
            [
                'item_id' => $service->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 300,
            ],
        ],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $entry = generatedEntry($order);

    /** El flete contratado en la misma orden no se recibe en una bodega. */
    expect($entry->lines)->toHaveCount(1);
    expect($entry->lines->first()->item_id)->toBe($item->id);
});

test('an order with nothing to receive generates no entry at all', function () {
    [$user, $company, $supplier, $warehouse, , $unit] = purchaseReturnScenario();

    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
    ]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $service, $unit, [
        'lines' => [[
            'item_id' => $service->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 300,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    expect(generatedEntry($order))->toBeNull();
});

test('the generated entry moves no stock: it is the entry that must be confirmed', function () {
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

    $entry = generatedEntry($order);

    expect(entryMovements($entry))->toHaveCount(0);

    /** Lo único que aprobar movió es lo anunciado, como ya hacía antes. */
    $stock = stockAt($item, $location);
    expect((float) $stock->quantity)->toBe(0.0);
    expect((float) $stock->incoming_quantity)->toBe(10.0);

    /** Y confirmar la entrada sí mete la mercancía. */
    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    expect(entryMovements($entry))->toHaveCount(1);
    expect((float) stockAt($item, $location)->quantity)->toBe(10.0);
});

test('a serialized item travels without serials and the entry demands them when confirmed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    Item::where('id', $item->id)->update(['type' => 'serialized']);

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 3,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $entry = generatedEntry($order);

    /** El borrador trae la cantidad, no las series: nadie ha visto las cajas. */
    expect((float) $entry->lines->first()->quantity)->toBe(3.0);
    expect($entry->lines->first()->serials)->toHaveCount(0);

    /** Y sin ellas la mercancía no se mueve. */
    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasErrors('status');
    expect($entry->refresh()->status)->toBe('draft');
    expect(entryMovements($entry))->toHaveCount(0);

    loadEntrySerials($user, $company, $entry, ['S-1', 'S-2', 'S-3']);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();
    expect(entryMovements($entry))->toHaveCount(3);
});

test('cancelling the order cancels the entry it had generated', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $entry = generatedEntry($order);

    movePurchaseOrderTo($user, $company, $order, 'cancelled', [
        'cancellation_reason' => 'El proveedor no pudo servirla.',
    ])->assertSessionHasNoErrors();

    expect($entry->refresh()->status)->toBe('cancelled');
});

test('an order whose entry is already confirmed cannot be cancelled', function () {
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

    $entry = generatedEntry($order);
    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    /** La mercancía ya entró: la orden no se anula hasta anular la entrada. */
    movePurchaseOrderTo($user, $company, $order, 'cancelled', [
        'cancellation_reason' => 'El proveedor no pudo servirla.',
    ])->assertSessionHasErrors('status');

    expect($order->refresh()->status)->toBe('confirmed');
    /** Y la existencia no se tocó al intentarlo. */
    expect((float) stockAt($item, $location)->quantity)->toBe(10.0);

    /** Anulada la entrada, la orden sí se anula. */
    moveEntryTo($user, $company, $entry, 'cancelled')->assertSessionHasNoErrors();

    movePurchaseOrderTo($user, $company, $order, 'cancelled', [
        'cancellation_reason' => 'El proveedor no pudo servirla.',
    ])->assertSessionHasNoErrors();

    expect($order->refresh()->status)->toBe('cancelled');
});

test('an order that already had its entry made by hand does not get a second one', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    $manual = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    $entries = Entry::where('sourceable_id', $order->id)->get();

    expect($entries)->toHaveCount(1);
    expect($entries->first()->id)->toBe($manual->id);
});
