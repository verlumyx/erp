<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

use function Pest\Laravel\actingAs;

/**
 * El avance del pedido de venta no se declara: lo escriben los documentos que
 * lo cumplen. El Despacho saca la mercancía y la Factura de venta reconoce la
 * deuda, y el pedido solo se cierra cuando las dos llegaron a lo pedido.
 *
 * Es el espejo de `PurchaseOrderFulfillmentStatusTest` en compras.
 */

/** Un pedido confirmado de 10 unidades, la cantidad con la que cuentan estos tests. */
function tenUnitSalesOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
): SalesOrder {
    return confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]],
    ]);
}

/** El despacho que el pedido generó al confirmarse. */
function mirrorDispatchOf(SalesOrder $order): ?Dispatch
{
    return Dispatch::query()
        ->with('lines')
        ->where('sourceable_type', SalesOrder::MORPH_ALIAS)
        ->where('sourceable_id', $order->id)
        ->first();
}

/** Factura `$quantity` del pedido, y emite la factura. */
function invoiceAgainstSalesOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    SalesOrder $order,
    float $quantity,
): SalesInvoice {
    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => $quantity,
            'unit_price' => 100,
            'sourceable_type' => SalesOrderLine::MORPH_ALIAS,
            'sourceable_id' => $order->lines->first()->id,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    return $invoice->refresh();
}

test('the screen cannot declare the order partial or completed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = tenUnitSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    moveSalesOrderTo($user, $company, $order, 'partial')->assertSessionHasErrors('status');
    expect($order->refresh()->status)->toBe('confirmed');

    moveSalesOrderTo($user, $company, $order, 'completed')->assertSessionHasErrors('status');
    expect($order->refresh()->status)->toBe('confirmed');
});

test('invoicing part of the order moves it to partial by itself', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = tenUnitSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    invoiceAgainstSalesOrder($user, $company, $client, $warehouse, $item, $unit, $order, 4);

    expect($order->refresh()->status)->toBe('partial');
    expect((float) $order->invoiced_percent)->toBe(40.0);
});

test('an order fully dispatched but not invoiced is still open', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = tenUnitSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    moveDispatchTo($user, $company, mirrorDispatchOf($order), 'confirmed')->assertSessionHasNoErrors();

    /** La mercancía salió, pero al cliente todavía no se le ha facturado. */
    expect($order->refresh()->status)->toBe('partial');
    expect((float) $order->dispatched_percent)->toBe(100.0);
});

test('the order closes on its own once everything left and was invoiced', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = tenUnitSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    moveDispatchTo($user, $company, mirrorDispatchOf($order), 'confirmed')->assertSessionHasNoErrors();
    invoiceAgainstSalesOrder($user, $company, $client, $warehouse, $item, $unit, $order, 10);

    $order->refresh();
    expect($order->status)->toBe('completed');
    expect((float) $order->dispatched_percent)->toBe(100.0);
    expect((float) $order->invoiced_percent)->toBe(100.0);
});

test('cancelling the documents walks the closed order back to confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = tenUnitSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $dispatch = mirrorDispatchOf($order);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();
    $invoice = invoiceAgainstSalesOrder($user, $company, $client, $warehouse, $item, $unit, $order, 10);

    expect($order->refresh()->status)->toBe('completed');

    moveDispatchTo($user, $company, $dispatch, 'cancelled')->assertSessionHasNoErrors();

    /** Se deshizo la mitad del cumplimiento: el pedido vuelve a estar a medias. */
    expect($order->refresh()->status)->toBe('partial');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Factura equivocada.'],
        )
        ->assertSessionHasNoErrors();

    /** Sin nada despachado ni facturado, el pedido es otra vez un pedido confirmado. */
    expect($order->refresh()->status)->toBe('confirmed');
});

/** Un artículo que no lleva existencia: una instalación, un flete, una garantía. */
function salesServiceItem(
    \App\Modules\Company\Models\Company $company,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
): Item {
    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
    ]);

    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    return $service;
}

test('what carries no stock does not hold the dispatched percent back', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $service = salesServiceItem($company, $unit);

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 100,
            ],
            /** La instalación que se vende con el equipo: no sale en el camión. */
            [
                'item_id' => $service->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 50,
            ],
        ],
    ]);

    moveDispatchTo($user, $company, mirrorDispatchOf($order), 'confirmed')->assertSessionHasNoErrors();

    /**
     * Toda la mercancía salió. Contando la instalación el avance se quedaría en
     * 50 %, y el pedido no podría cerrarse nunca.
     */
    expect((float) $order->refresh()->dispatched_percent)->toBe(100.0);
});

test('an order of services alone has nothing to dispatch and closes with its invoice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $service = salesServiceItem($company, $unit);

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $service->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 1,
            'unit_price' => 300,
        ]],
    ]);

    /** No hay despacho que esperar: ese lado está cumplido de nacimiento. */
    expect((float) $order->refresh()->dispatched_percent)->toBe(100.0);
    expect($order->status)->toBe('confirmed');

    invoiceAgainstSalesOrder($user, $company, $client, $warehouse, $service, $unit, $order, 1);

    /** Facturado el servicio, el pedido no espera nada más. */
    expect($order->refresh()->status)->toBe('completed');
});
