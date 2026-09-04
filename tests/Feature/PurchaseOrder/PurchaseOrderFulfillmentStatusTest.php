<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

use function Pest\Laravel\actingAs;

/**
 * El avance de la orden de compra no se declara: lo escriben los documentos que
 * la cumplen. La Entrada trae la mercancía y la Factura de compra reconoce la
 * deuda, y la orden solo se cierra cuando las dos llegaron a lo pedido.
 */

/** Una orden de 10 unidades ya confirmada, lista para recibir y facturar. */
function confirmedPurchaseOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
): PurchaseOrder {
    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    return $order->refresh()->load('lines');
}

/** Recibe `$quantity` de la orden con una entrada propia, y la confirma. */
function receiveAgainstOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    \App\Modules\WarehouseLocation\Models\WarehouseLocation $location,
    PurchaseOrder $order,
    float $quantity,
): \App\Modules\Entry\Models\Entry {
    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => $quantity,
            'unit_price' => 25,
            'location_id' => $location->id,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    return $entry;
}

/** Factura `$quantity` de la orden, y confirma la factura. */
function invoiceAgainstOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    PurchaseOrder $order,
    float $quantity,
): PurchaseInvoice {
    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit, [
        'sourceable_type' => PurchaseOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => $quantity,
            'unit_price' => 25,
            'sourceable_type' => PurchaseOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    return $invoice;
}

test('the screen cannot declare the order partial or completed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = confirmedPurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    movePurchaseOrderTo($user, $company, $order, 'partial')->assertSessionHasErrors('status');
    expect($order->refresh()->status)->toBe('confirmed');

    movePurchaseOrderTo($user, $company, $order, 'completed')->assertSessionHasErrors('status');
    expect($order->refresh()->status)->toBe('confirmed');
});

test('receiving part of the order moves it to partial by itself', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = confirmedPurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    receiveAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $location, $order, 4);

    expect($order->refresh()->status)->toBe('partial');
    expect((float) $order->received_percent)->toBe(40.0);
});

test('an order fully received but not invoiced is still open', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = confirmedPurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    receiveAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $location, $order, 10);

    /** La mercancía está en la bodega, pero el proveedor todavía no ha facturado. */
    expect($order->refresh()->status)->toBe('partial');
    expect((float) $order->received_percent)->toBe(100.0);
});

test('an order invoiced but not received is still open', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $order = confirmedPurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    invoiceAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $order, 10);

    expect($order->refresh()->status)->toBe('partial');
    expect((float) $order->invoiced_percent)->toBe(100.0);
});

test('the order closes on its own once everything arrived and was invoiced', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = confirmedPurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    receiveAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $location, $order, 10);
    invoiceAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $order, 10);

    $order->refresh();
    expect($order->status)->toBe('completed');
    expect((float) $order->received_percent)->toBe(100.0);
    expect((float) $order->invoiced_percent)->toBe(100.0);
});

test('cancelling the entry walks the closed order back to confirmed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = confirmedPurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    $entry = receiveAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $location, $order, 10);
    $invoice = invoiceAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $order, 10);

    expect($order->refresh()->status)->toBe('completed');

    moveEntryTo($user, $company, $entry, 'cancelled')->assertSessionHasNoErrors();

    /** Se deshizo la mitad del cumplimiento: la orden vuelve a estar a medias. */
    expect($order->refresh()->status)->toBe('partial');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Factura equivocada.'],
        )
        ->assertSessionHasNoErrors();

    /** Sin nada recibido ni facturado, la orden es otra vez una orden confirmada. */
    expect($order->refresh()->status)->toBe('confirmed');
});

test('a draft order is never touched by what a document receives', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
        ]],
    ]);

    receiveAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $location, $order, 10);

    /** El avance se apunta igual, pero una orden sin confirmar no avanza de estado. */
    expect($order->refresh()->status)->toBe('draft');
    expect((float) $order->received_percent)->toBe(100.0);
});

/** Un artículo que no lleva existencia: un flete, una comisión, una instalación. */
function purchaseServiceItem(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
): Item {
    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
        'is_purchasable' => 'yes',
    ]);

    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    return $service;
}

test('what carries no stock does not hold the received percent back', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = purchaseReturnScenario();

    $service = purchaseServiceItem($company, $unit);

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 25,
            ],
            /** El flete de esa compra: se factura, pero no llega a ninguna bodega. */
            [
                'item_id' => $service->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 30,
            ],
        ],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    receiveAgainstOrder($user, $company, $supplier, $warehouse, $item, $unit, $location, $order, 10);

    /**
     * Toda la mercancía llegó. Contando el flete el avance se quedaría en 50 %,
     * y la orden no podría cerrarse nunca.
     */
    expect((float) $order->refresh()->received_percent)->toBe(100.0);
});

test('an order of services alone has nothing to receive and closes with its invoice', function () {
    [$user, $company, $supplier, $warehouse, , $unit] = purchaseReturnScenario();

    $service = purchaseServiceItem($company, $unit);

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $service, $unit, [
        'lines' => [[
            'item_id' => $service->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 300,
        ]],
    ]);

    movePurchaseOrderTo($user, $company, $order, 'confirmed')->assertSessionHasNoErrors();

    /** No hay entrada que esperar: ese lado está cumplido de nacimiento. */
    expect((float) $order->refresh()->received_percent)->toBe(100.0);
    expect($order->status)->toBe('confirmed');

    invoiceAgainstOrder($user, $company, $supplier, $warehouse, $service, $unit, $order, 1);

    /** Facturado el servicio, la orden no espera nada más. */
    expect($order->refresh()->status)->toBe('completed');
});
