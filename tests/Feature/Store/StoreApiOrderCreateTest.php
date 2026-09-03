<?php

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;

function webOrderPayload(string $slug, array $overrides = []): array
{
    return [
        'delivery_address' => 'Av. Principal, casa 12',
        'delivery_city' => 'Valencia',
        'delivery_state' => 'Carabobo',
        'buyer_notes' => 'Llamar antes de entregar',
        'lines' => [
            ['slug' => $slug, 'quantity' => 2],
        ],
        ...$overrides,
    ];
}

test('sin token responde 401', function () {
    $scenario = storeOrderScenario();

    $this->withHeaders(storeApiHeaders())
        ->postJson(route('api.store.orders.store'), webOrderPayload($scenario['store_item']->slug))
        ->assertUnauthorized();
});

test('con allows_orders en no responde 403', function () {
    $scenario = storeOrderScenario(['allows_orders' => 'no']);

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), webOrderPayload($scenario['store_item']->slug))
        ->assertForbidden();
});

test('crea el pedido pendiente con el precio de la lista de la tienda, la tasa congelada y el código PWE', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->withDocument('V', '11222333')->create([
        'company_id' => $scenario['company']->id,
        'phone' => '+58 414 000 0000',
    ]);

    $response = $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), webOrderPayload($scenario['store_item']->slug, [
            'lines' => [['slug' => $scenario['store_item']->slug, 'quantity' => 3]],
        ]));

    $response->assertCreated()
        ->assertJsonPath('data.code', 'PWE000001')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.total', '135.00');

    $order = StoreOrder::query()->with('lines')->where('code', 'PWE000001')->firstOrFail();

    expect($order->store_customer_id)->toBe($customer->id)
        ->and($order->client_id)->toBeNull()
        ->and($order->buyer_name)->toBe($customer->name)
        ->and($order->buyer_email)->toBe($customer->email)
        ->and($order->buyer_phone)->toBe('+58 414 000 0000')
        ->and($order->buyer_document_type)->toBe('V')
        ->and($order->buyer_document_number)->toBe('11222333')
        ->and((float) $order->exchange_rate)->toBe(36.5)
        ->and((string) $order->subtotal)->toBe('135.00')
        ->and($order->delivery_address)->toBe('Av. Principal, casa 12')
        ->and($order->buyer_notes)->toBe('Llamar antes de entregar')
        ->and($order->lines)->toHaveCount(1);

    $line = $order->lines->first();

    expect($line->item_id)->toBe($scenario['item']->id)
        ->and($line->store_item_id)->toBe($scenario['store_item']->id)
        ->and($line->measurement_unit_id)->toBe($scenario['unit']->id)
        ->and((float) $line->unit_price)->toBe(45.0)
        ->and((float) $line->list_price)->toBe(45.0)
        ->and((float) $line->tax_percent)->toBe(0.0)
        ->and((string) $line->subtotal)->toBe('135.00');
});

test('los precios que manda la tienda se ignoran: manda la lista', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), webOrderPayload($scenario['store_item']->slug, [
            'lines' => [['slug' => $scenario['store_item']->slug, 'quantity' => 1, 'unit_price' => 1]],
        ]))
        ->assertCreated()
        ->assertJsonPath('data.total', '45.00');
});

test('un comprador vinculado usa la lista de su cliente y copia el client_id', function () {
    $scenario = storeOrderScenario();

    $clientList = PriceList::factory()->create(['company_id' => $scenario['company']->id]);

    ItemPrice::factory()->create([
        'company_id' => $scenario['company']->id,
        'item_id' => $scenario['item']->id,
        'price_list_id' => $clientList->id,
        'price' => 40,
        'currency' => 'USD',
    ]);

    $client = Client::factory()->create([
        'company_id' => $scenario['company']->id,
        'price_list_id' => $clientList->id,
    ]);

    $address = ClientAddress::factory()->create(['company_id' => $scenario['company']->id, 'client_id' => $client->id]);

    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), [
            'client_address_id' => $address->id,
            'lines' => [['slug' => $scenario['store_item']->slug, 'quantity' => 2]],
        ])
        ->assertCreated()
        ->assertJsonPath('data.total', '80.00');

    $order = StoreOrder::query()->firstOrFail();

    expect($order->client_id)->toBe($client->id)
        ->and($order->client_address_id)->toBe($address->id)
        ->and($order->delivery_address)->toBeNull();
});

test('una dirección que no es del cliente vinculado responde 422', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $otherClient = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $address = ClientAddress::factory()->create(['company_id' => $scenario['company']->id, 'client_id' => $otherClient->id]);

    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), [
            'client_address_id' => $address->id,
            'lines' => [['slug' => $scenario['store_item']->slug, 'quantity' => 2]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['client_address_id']);
});

test('sin dirección elegida ni escrita responde 422', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), [
            'lines' => [['slug' => $scenario['store_item']->slug, 'quantity' => 2]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['delivery_address']);
});

test('un producto que dejó de ser visible responde 422 con la línea afectada', function () {
    $scenario = storeOrderScenario();

    $scenario['store_item']->update(['status' => 'inactive']);

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), webOrderPayload($scenario['store_item']->slug))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['lines.0.slug']);

    expect(StoreOrder::query()->count())->toBe(0);
});

test('un producto sin precio en la lista responde 422 con la línea afectada', function () {
    $scenario = storeOrderScenario();

    ItemPrice::query()->where('item_id', $scenario['item']->id)->update(['status' => 'inactive']);

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), webOrderPayload($scenario['store_item']->slug))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['lines.0.slug']);
});

test('no valida existencia: sin stock el pedido igual entra', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), webOrderPayload($scenario['store_item']->slug, [
            'lines' => [['slug' => $scenario['store_item']->slug, 'quantity' => 500]],
        ]))
        ->assertCreated();
});

test('un carrito vacío responde 422', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->postJson(route('api.store.orders.store'), webOrderPayload($scenario['store_item']->slug, ['lines' => []]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['lines']);
});
