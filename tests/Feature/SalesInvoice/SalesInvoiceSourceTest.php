<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesOrder\Models\SalesOrder;

use function Pest\Laravel\actingAs;

/**
 * Un pedido confirmado, listo para facturarse. Confirmarlo reserva existencia,
 * así que la bodega tiene que tener con qué.
 */
function confirmedSalesOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Client\Models\Client $client,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): SalesOrder {
    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, $overrides);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update-status', ['company' => $company->id, 'id' => $order->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    return $order->refresh()->load('lines');
}

test('an invoice remembers the sales order that originated it through the morph map', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'sourceable_type' => 'sales_order_line',
                'sourceable_id' => $orderLine->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 2,
                'unit_price' => 100,
            ],
        ],
    ]);

    /** Se guarda el alias del mapa, nunca el nombre de la clase. */
    expect($invoice->sourceable_type)->toBe('sales_order');
    expect($invoice->sourceable_id)->toBe($order->id);
    expect($invoice->sourceable->id)->toBe($order->id);
    expect($invoice->lines->first()->sourceable_type)->toBe('sales_order_line');

    /** El pedido las expone de vuelta con `morphMany`. */
    expect($order->salesInvoices()->pluck('id')->all())->toBe([$invoice->id]);
});

test('issuing the invoice advances the invoiced quantity of the order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 100],
        ],
    ]);
    $orderLine = $order->lines->first();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'sourceable_type' => 'sales_order_line',
                'sourceable_id' => $orderLine->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'unit_price' => 100,
            ],
        ],
    ]);

    /** Un borrador todavía no factura nada. */
    expect((float) $orderLine->refresh()->invoiced_quantity)->toBe(0.0);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    expect((float) $orderLine->refresh()->invoiced_quantity)->toBe(4.0);
    expect((float) $order->refresh()->invoiced_percent)->toBe(40.0);
});

test('cancelling the invoice gives the invoiced quantity back to the order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 100],
        ],
    ]);
    $orderLine = $order->lines->first();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'sourceable_type' => 'sales_order_line',
                'sourceable_id' => $orderLine->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 100,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    expect((float) $order->refresh()->invoiced_percent)->toBe(100.0);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'Se anuló la venta.'],
        )
        ->assertSessionHasNoErrors();

    expect((float) $orderLine->refresh()->invoiced_quantity)->toBe(0.0);
    expect((float) $order->refresh()->invoiced_percent)->toBe(0.0);
});

test('a source type outside the morph map is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => 'purchase_order',
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_type');

    expect(SalesInvoice::count())->toBe(0);
});

test('the source id alone, without its type, is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_type');
});

test('an order of another client cannot be invoiced', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);
    $order = confirmedSalesOrder($user, $company, $other, $warehouse, $item, $unit);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_id');
});

test('a line cannot come from an order the invoice does not come from', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $orderLine = $order->lines->first();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'sourceable_type' => 'sales_order_line',
                'sourceable_id' => $orderLine->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.sourceable_id');
});

test('a line of another order is rejected', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $another = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'sourceable_type' => 'sales_order_line',
                'sourceable_id' => $another->lines->first()->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.sourceable_id');
});

test('a cancelled order cannot be invoiced', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update-status', ['company' => $company->id, 'id' => $order->id]),
            ['status' => 'cancelled', 'cancellation_reason' => 'El cliente desistió.'],
        )
        ->assertSessionHasNoErrors();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('sourceable_id');
});

test('a line cannot invoice more than the order has left', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 100],
        ],
    ]);
    $orderLine = $order->lines->first();

    /** Seis ya facturadas dejan cuatro por facturar. */
    $orderLine->update(['invoiced_quantity' => 6]);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, [
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'sourceable_type' => 'sales_order_line',
                'sourceable_id' => $orderLine->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 5,
                'unit_price' => 100,
            ],
        ],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.quantity');
});

test('a line can invoice exactly what the order has left', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $order = confirmedSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 100],
        ],
    ]);
    $orderLine = $order->lines->first();

    $orderLine->update(['invoiced_quantity' => 6]);

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
        'lines' => [
            [
                'sourceable_type' => 'sales_order_line',
                'sourceable_id' => $orderLine->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'unit_price' => 100,
            ],
        ],
    ]);

    expect((float) $invoice->lines->first()->quantity)->toBe(4.0);
});
