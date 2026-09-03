<?php

use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;

use function Pest\Laravel\actingAs;

function pendingWebOrder(array $scenario): StoreOrder
{
    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    return StoreOrder::factory()->create([
        'company_id' => $scenario['company']->id,
        'store_customer_id' => $customer->id,
    ]);
}

test('rechazar exige motivo', function () {
    $scenario = storeOrderScenario();
    $order = pendingWebOrder($scenario);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from('/')
        ->put(route('store-orders.reject', ['company' => $scenario['company']->id, 'id' => $order->id]), [
            'rejection_reason' => '',
        ])
        ->assertSessionHasErrors(['rejection_reason']);

    expect($order->fresh()->status)->toBe('pending');
});

test('rechazar guarda el motivo y la fecha', function () {
    $scenario = storeOrderScenario();
    $order = pendingWebOrder($scenario);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from('/')
        ->put(route('store-orders.reject', ['company' => $scenario['company']->id, 'id' => $order->id]), [
            'rejection_reason' => 'Producto descontinuado',
        ])
        ->assertSessionHasNoErrors();

    $order->refresh();

    expect($order->status)->toBe('rejected')
        ->and($order->rejection_reason)->toBe('Producto descontinuado')
        ->and($order->rejected_at)->not->toBeNull();
});

test('solo un pedido pendiente se rechaza', function () {
    $scenario = storeOrderScenario();
    $order = pendingWebOrder($scenario);
    $order->update(['status' => 'converted', 'converted_at' => now()]);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->from('/')
        ->put(route('store-orders.reject', ['company' => $scenario['company']->id, 'id' => $order->id]), [
            'rejection_reason' => 'Tarde',
        ])
        ->assertSessionHasErrors(['status']);

    expect($order->fresh()->status)->toBe('converted');
});

test('el listado y el detalle de pedidos web se renderizan con sus props', function () {
    $scenario = storeOrderScenario();
    $order = pendingWebOrder($scenario);

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->get(route('store-orders.index', ['company' => $scenario['company']->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/orders/index')
            ->has('store_orders', 1)
            ->where('pending_count', 1));

    actingAs($scenario['user'])
        ->withSession(['current_company_id' => $scenario['company']->id])
        ->get(route('store-orders.show', ['company' => $scenario['company']->id, 'id' => $order->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/orders/show')
            ->where('store_order.id', $order->id)
            ->where('suggested_client', null)
            ->where('customer_needs_document', true));
});
