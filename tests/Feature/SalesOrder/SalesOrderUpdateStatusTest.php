<?php

declare(strict_types=1);

use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

use function Pest\Laravel\actingAs;

/**
 * @param  array<string, mixed>  $payload
 */
function putStatus(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    SalesOrder $order,
    array $payload,
): \Illuminate\Testing\TestResponse {
    return actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-orders.update-status', ['company' => $company->id, 'id' => $order->id]),
            $payload,
        );
}

test('a draft sales order can be confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $response = putStatus($user, $company, $order, ['status' => 'confirmed']);

    $response->assertRedirect(
        route('sales-orders.show', ['company' => $company->id, 'id' => $order->id]),
    );
    $response->assertSessionHasNoErrors();

    $confirmed = SalesOrder::find($order->id);
    expect($confirmed->status)->toBe('confirmed');
    expect($confirmed->approved_by)->toBe($user->id);
    expect($confirmed->approved_at)->not->toBeNull();
});

test('a sales order can be cancelled with a reason', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, [
        'status' => 'cancelled',
        'cancellation_reason' => 'El cliente desistió del pedido.',
    ])->assertSessionHasNoErrors();

    $cancelled = SalesOrder::find($order->id);
    expect($cancelled->status)->toBe('cancelled');
    expect($cancelled->cancelled_at)->not->toBeNull();
    expect($cancelled->cancellation_reason)->toBe('El cliente desistió del pedido.');
});

test('confirming reserves the stock and cancelling releases it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $location = stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $stock = ItemStock::where('item_id', $item->id)->where('location_id', $location->id)->firstOrFail();
    expect((float) $stock->reserved_quantity)->toBe(0.0);
    expect((float) $stock->available_quantity)->toBe(100.0);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertSessionHasNoErrors();

    /** El pedido no descarga: compromete. */
    $stock->refresh();
    expect((float) $stock->quantity)->toBe(100.0);
    expect((float) $stock->reserved_quantity)->toBe(2.0);
    expect((float) $stock->available_quantity)->toBe(98.0);
    expect((float) SalesOrderLine::where('sales_order_id', $order->id)->value('reserved_quantity'))
        ->toBe(2.0);

    putStatus($user, $company, $order, [
        'status' => 'cancelled',
        'cancellation_reason' => 'Sin stock disponible.',
    ])->assertSessionHasNoErrors();

    $stock->refresh();
    expect((float) $stock->reserved_quantity)->toBe(0.0);
    expect((float) $stock->available_quantity)->toBe(100.0);
    expect((float) SalesOrderLine::where('sales_order_id', $order->id)->value('reserved_quantity'))
        ->toBe(0.0);
});

test('an order the warehouse cannot cover is not confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item, 1);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');

    expect(SalesOrder::find($order->id)->status)->toBe('draft');
});

test('two orders cannot reserve the same unit twice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item, 3);

    $first = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    $second = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $first, ['status' => 'confirmed'])->assertSessionHasNoErrors();

    /** Quedan 1 disponible de 3: el segundo pedido de 2 ya no cabe. */
    putStatus($user, $company, $second, ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');
});

test('cancelling requires a reason', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, ['status' => 'cancelled'])
        ->assertSessionHasErrors('cancellation_reason');

    expect(SalesOrder::find($order->id)->status)->toBe('draft');
});

test('a draft order cannot jump straight to completed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, ['status' => 'completed'])
        ->assertSessionHasErrors('status');

    expect(SalesOrder::find($order->id)->status)->toBe('draft');
});

test('a cancelled order is final', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, [
        'status' => 'cancelled',
        'cancellation_reason' => 'Duplicado.',
    ])->assertSessionHasNoErrors();

    putStatus($user, $company, $order, ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');

    expect(SalesOrder::find($order->id)->status)->toBe('cancelled');
});

test('a confirmed order cannot be declared partial or completed by hand', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertSessionHasNoErrors();

    /**
     * El avance lo escriben el Despacho y la Factura de venta al confirmarse;
     * por esta ruta solo pasan las decisiones del usuario.
     * Ver `SalesOrderFulfillmentStatusTest`.
     */
    foreach (['partial', 'completed'] as $status) {
        putStatus($user, $company, $order, ['status' => $status])->assertSessionHasErrors('status');

        expect(SalesOrder::find($order->id)->status)->toBe('confirmed');
    }
});

test('an order without active lines cannot be confirmed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    SalesOrderLine::where('sales_order_id', $order->id)->update(['status' => 'inactive']);

    putStatus($user, $company, $order, ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['sales-orders.list']);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertForbidden();
});
