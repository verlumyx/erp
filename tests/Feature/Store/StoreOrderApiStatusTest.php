<?php

use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Models\StoreOrderLine;

test('consulta el pedido por código con el token del comprador', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $order = StoreOrder::factory()->create([
        'company_id' => $scenario['company']->id,
        'store_customer_id' => $customer->id,
        'subtotal' => 90,
        'total' => 90,
    ]);

    StoreOrderLine::factory()->create([
        'store_order_id' => $order->id,
        'company_id' => $scenario['company']->id,
        'item_id' => $scenario['item']->id,
        'store_item_id' => $scenario['store_item']->id,
        'measurement_unit_id' => $scenario['unit']->id,
        'quantity' => 2,
        'unit_price' => 45,
        'subtotal' => 90,
        'total' => 90,
    ]);

    $this->withHeaders(storeApiHeaders($customer))
        ->getJson(route('api.store.orders.show', ['code' => $order->code]))
        ->assertOk()
        ->assertJsonPath('data.code', $order->code)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.total', '90.00')
        ->assertJsonCount(1, 'data.lines')
        ->assertJsonPath('data.lines.0.slug', $scenario['store_item']->slug)
        ->assertJsonPath('data.lines.0.quantity', '2.0000');
});

test('el pedido de otro comprador responde 404', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);
    $other = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $order = StoreOrder::factory()->create([
        'company_id' => $scenario['company']->id,
        'store_customer_id' => $other->id,
    ]);

    $this->withHeaders(storeApiHeaders($customer))
        ->getJson(route('api.store.orders.show', ['code' => $order->code]))
        ->assertNotFound();
});

test('un código inexistente responde 404', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->getJson(route('api.store.orders.show', ['code' => 'PWE999999']))
        ->assertNotFound();
});
