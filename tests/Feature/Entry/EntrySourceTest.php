<?php

declare(strict_types=1);

use App\Modules\Entry\Models\Entry;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

test('an entry can be born from a purchase order and traces each line to its own', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 4,
            'unit_price' => 25,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    expect($entry->sourceable_type)->toBe(PurchaseOrder::MORPH_ALIAS);
    expect($entry->sourceable_id)->toBe($order->id);
    expect($entry->sourceable)->toBeInstanceOf(PurchaseOrder::class);
    expect($entry->lines->first()->sourceable_id)->toBe($orderLine->id);

    /** En borrador la orden todavía no sabe nada: nada ha llegado. */
    expect((float) $orderLine->refresh()->received_quantity)->toBe(0.0);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $orderLine->refresh();
    expect((float) $orderLine->received_quantity)->toBe(4.0);
    expect((float) $orderLine->pending_quantity)->toBe(6.0);
    expect((float) $order->refresh()->received_percent)->toBe(40.0);
});

test('cancelling the entry gives the order its pending quantity back', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();
    expect((float) $order->refresh()->received_percent)->toBe(100.0);

    moveEntryTo($user, $company, $entry, 'cancelled')->assertSessionHasNoErrors();

    $orderLine->refresh();
    expect((float) $orderLine->received_quantity)->toBe(0.0);
    expect((float) $orderLine->pending_quantity)->toBe(10.0);
    expect((float) $order->refresh()->received_percent)->toBe(0.0);
});

test('only what the inspection accepted counts against the order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'inspection_status' => 'partial',
        'inspected_by' => $user->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'rejected_quantity' => 3,
            'rejection_reason' => 'Tres unidades sin sello.',
            'unit_price' => 25,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    /** Lo rechazado se le devuelve al proveedor: sigue pendiente de llegar. */
    expect((float) $orderLine->refresh()->received_quantity)->toBe(7.0);
    expect((float) $orderLine->pending_quantity)->toBe(3.0);
});

test('receiving more than the order has pending is refused without the permission', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    /** El almacenista registra entradas, pero no decide aceptar de más. */
    assignRoleWithPermissions($user, $company, [
        'entries.list', 'entries.show', 'entries.create', 'entries.update', 'entries.update-status',
    ]);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 12,
            'unit_price' => 25,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.quantity');

    expect(Entry::count())->toBe(0);
});

test('with the permission the supplier may deliver more than what was ordered', function () {
    /** El usuario de la fixture tiene acceso total, permiso incluido. */
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 12,
            'unit_price' => 25,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    expect((float) $orderLine->refresh()->received_quantity)->toBe(12.0);
    /** Nada queda pendiente, y el avance no pasa del 100 %. */
    expect((float) $orderLine->pending_quantity)->toBe(0.0);
    expect((float) $order->refresh()->received_percent)->toBe(100.0);
});

test('two entries against the same order line share its pending quantity', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $line = [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 6,
        'unit_price' => 25,
        'location_id' => $location->id,
        'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
        'sourceable_id' => $orderLine->id,
    ];

    createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [$line],
    ]);

    /** Sin el permiso de recibir de más, el pendiente es un techo. */
    assignRoleWithPermissions($user, $company, [
        'entries.list', 'entries.show', 'entries.create', 'entries.update', 'entries.update-status',
    ]);

    /** De las 10 pedidas ya se comprometieron 6: solo caben 4 más. */
    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[...$line, 'quantity' => 5]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.quantity');

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[...$line, 'quantity' => 4]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();
});

test('a cancelled entry frees the pending quantity it was holding', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $line = [
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 10,
        'unit_price' => 25,
        'location_id' => $location->id,
        'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
        'sourceable_id' => $orderLine->id,
    ];

    $first = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [$line],
    ]);

    moveEntryTo($user, $company, $first, 'cancelled')->assertSessionHasNoErrors();

    /** Sin el permiso de recibir de más, el pendiente vuelve a ser un techo. */
    assignRoleWithPermissions($user, $company, [
        'entries.list', 'entries.show', 'entries.create', 'entries.update', 'entries.update-status',
    ]);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [$line],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();
});

test('a line is received in the same unit in which it was ordered', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'conversion_factor' => 12,
        'is_base' => 'no',
    ]);

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 1,
            'unit_price' => 300,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $orderLine->id,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('the source order has to belong to the same supplier', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);
    $order = sourcePurchaseOrder($user, $company, $other, $warehouse, $item, $unit);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_id');
});

test('a cancelled purchase order cannot be received', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    PurchaseOrder::where('id', $order->id)->update(['status' => 'cancelled']);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_id');
});

test('a line cannot trace to an order line without an order in the header', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 25,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.sourceable_id');
});

test('a line cannot trace to an order line of another order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);
    $another = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    $payload = entryPayload($supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 25,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $another->lines->first()->id,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('entries.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.sourceable_id');
});
