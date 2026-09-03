<?php

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreOrder;

test('me devuelve los datos del comprador y las direcciones activas de su cliente', function () {
    $scenario = storeOrderScenario();

    $client = Client::factory()->create(['company_id' => $scenario['company']->id]);
    $active = ClientAddress::factory()->create(['company_id' => $scenario['company']->id, 'client_id' => $client->id]);
    ClientAddress::factory()->create(['company_id' => $scenario['company']->id, 'client_id' => $client->id, 'status' => 'inactive']);

    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->getJson(route('api.store.customers.me'))
        ->assertOk()
        ->assertJsonPath('data.id', $customer->id)
        ->assertJsonPath('data.is_linked', true)
        ->assertJsonCount(1, 'data.addresses')
        ->assertJsonPath('data.addresses.0.id', $active->id);
});

test('un comprador sin vínculo no recibe direcciones', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->getJson(route('api.store.customers.me'))
        ->assertOk()
        ->assertJsonPath('data.is_linked', false)
        ->assertJsonCount(0, 'data.addresses');
});

test('sin token responde 401', function () {
    storeOrderScenario();

    $this->withHeaders(storeApiHeaders())
        ->getJson(route('api.store.customers.me'))
        ->assertUnauthorized();
});

test('un token sin la habilidad de la tienda no sirve', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);
    $token = $customer->createToken('otro', ['otra-cosa'])->plainTextToken;

    $this->withHeaders(['X-Store-Key' => 'stk_test-key', 'Authorization' => 'Bearer '.$token])
        ->getJson(route('api.store.customers.me'))
        ->assertUnauthorized();
});

test('orders lista solo los pedidos del comprador', function () {
    $scenario = storeOrderScenario();

    $customer = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);
    $other = StoreCustomer::factory()->create(['company_id' => $scenario['company']->id]);

    $mine = StoreOrder::factory()->create(['company_id' => $scenario['company']->id, 'store_customer_id' => $customer->id]);
    StoreOrder::factory()->create(['company_id' => $scenario['company']->id, 'store_customer_id' => $other->id]);

    $this->withHeaders(storeApiHeaders($customer))
        ->getJson(route('api.store.customers.orders'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', $mine->code);
});

test('el catálogo con token usa la lista de precio del cliente vinculado', function () {
    $scenario = storeOrderScenario();

    $clientList = PriceList::factory()->create(['company_id' => $scenario['company']->id]);

    ItemPrice::factory()->create([
        'company_id' => $scenario['company']->id,
        'item_id' => $scenario['item']->id,
        'price_list_id' => $clientList->id,
        'price' => 39,
        'currency' => 'USD',
    ]);

    $client = Client::factory()->create([
        'company_id' => $scenario['company']->id,
        'price_list_id' => $clientList->id,
    ]);

    $customer = StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $scenario['company']->id]);

    $catalog = app(\App\Modules\Store\Services\StoreCatalogService::class);

    expect($catalog->priceListFor($scenario['settings'], $customer))->toBe($clientList->id)
        ->and($catalog->priceListFor($scenario['settings'], null))->toBe($scenario['price_list']->id);

    $product = $catalog->product($scenario['settings'], $scenario['store_item'], $customer->fresh()->load('client'));

    expect($product['price']['amount'])->toBe('39.00');
});
