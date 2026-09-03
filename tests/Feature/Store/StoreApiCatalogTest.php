<?php

declare(strict_types=1);

use App\Modules\Category\Models\Category;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\Warehouse\Models\Warehouse;

use function Pest\Laravel\getJson;

function priceFor($company, $item, $priceList, float $price, string $currency = 'USD'): ItemPrice
{
    return ItemPrice::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'price_list_id' => $priceList->id,
        'price' => $price,
        'currency' => $currency,
    ]);
}

test('only active publications of active sellable items are listed', function () {
    [, $company, , , , $unit] = storeScenario();

    [, $visible] = publishedItem($company, $unit);
    publishedItem($company, $unit, [], ['status' => 'inactive']);
    publishedItem($company, $unit, ['status' => 'inactive']);
    publishedItem($company, $unit, ['is_sellable' => 'no']);

    $response = getJson(route('api.store.products.index'), storeKeyHeaders())->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.slug'))->toBe($visible->slug)
        ->and($response->json('data.0.unit.id'))->toBe($unit->id);
});

test('the price comes from the store list and is null without a row', function () {
    [, $company, , $priceList, , $unit] = storeScenario();

    [$priced, $pricedPub] = publishedItem($company, $unit);
    [, $unpricedPub] = publishedItem($company, $unit);
    priceFor($company, $priced, $priceList, 45);

    $data = collect(getJson(route('api.store.products.index'), storeKeyHeaders())->json('data'))->keyBy('slug');

    expect($data[$pricedPub->slug]['price'])->toBe(['amount' => '45.00', 'currency' => 'USD'])
        ->and($data[$unpricedPub->slug]['price'])->toBeNull();
});

test('without a configured list every price is null', function () {
    [, $company, $settings, $priceList, , $unit] = storeScenario();
    $settings->update(['price_list_id' => null]);

    [$item] = publishedItem($company, $unit);
    priceFor($company, $item, $priceList, 45);

    $response = getJson(route('api.store.products.index'), storeKeyHeaders())->assertOk();

    expect($response->json('data.0.price'))->toBeNull();
});

test('availability sums every warehouse or only the configured one', function () {
    [, $company, $settings, , $warehouse, $unit] = storeScenario();
    $other = Warehouse::factory()->create(['company_id' => $company->id]);
    [$item, $storeItem] = publishedItem($company, $unit);

    ItemStock::factory()->withBalance(5)->create(['company_id' => $company->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id]);
    ItemStock::factory()->withBalance(7)->create(['company_id' => $company->id, 'item_id' => $item->id, 'warehouse_id' => $other->id]);

    $settings->update(['shows_stock' => 'yes']);

    $availability = getJson(route('api.store.products.show', ['slug' => $storeItem->slug]), storeKeyHeaders())
        ->assertOk()
        ->json('data.availability');

    expect($availability)->toBe(['in_stock' => 'yes', 'quantity' => '12.0000']);

    $settings->update(['warehouse_id' => $warehouse->id]);

    $availability = getJson(route('api.store.products.show', ['slug' => $storeItem->slug]), storeKeyHeaders())
        ->json('data.availability');

    expect($availability)->toBe(['in_stock' => 'yes', 'quantity' => '5.0000']);
});

test('with shows_stock off only in_stock travels', function () {
    [, $company, , , $warehouse, $unit] = storeScenario();
    [$item, $storeItem] = publishedItem($company, $unit);
    [$empty, $emptyPub] = publishedItem($company, $unit);
    [, $servicePub] = publishedItem($company, $unit, ['type' => 'service']);

    ItemStock::factory()->withBalance(3)->create(['company_id' => $company->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id]);

    $data = collect(getJson(route('api.store.products.index'), storeKeyHeaders())->json('data'))->keyBy('slug');

    expect($data[$storeItem->slug]['availability'])->toBe(['in_stock' => 'yes'])
        ->and($data[$emptyPub->slug]['availability'])->toBe(['in_stock' => 'no'])
        ->and($data[$servicePub->slug]['availability'])->toBe(['in_stock' => 'yes']);
});

test('the secondary price uses the rate of the day and disappears when disabled', function () {
    [$user, $company, $settings, $priceList, , $unit] = storeScenario();
    [$item, $storeItem] = publishedItem($company, $unit);
    priceFor($company, $item, $priceList, 45);
    todayExchangeRate($company, $user, 'USD', 37.2);

    $secondary = getJson(route('api.store.products.show', ['slug' => $storeItem->slug]), storeKeyHeaders())
        ->assertOk()
        ->json('data.secondary_price');

    expect($secondary)->toBe([
        'amount' => '1674.00',
        'currency' => 'VES',
        'exchange_rate' => '37.20000000',
    ]);

    $settings->update(['shows_secondary_currency' => 'no']);

    expect(getJson(route('api.store.products.show', ['slug' => $storeItem->slug]), storeKeyHeaders())
        ->json('data.secondary_price'))->toBeNull();
});

test('without a rate of the day there is no secondary price', function () {
    [, $company, , $priceList, , $unit] = storeScenario();
    [$item, $storeItem] = publishedItem($company, $unit);
    priceFor($company, $item, $priceList, 45);

    expect(getJson(route('api.store.products.show', ['slug' => $storeItem->slug]), storeKeyHeaders())
        ->assertOk()
        ->json('data.secondary_price'))->toBeNull();
});

test('the settings endpoint publishes the rate of the day and the store currency', function () {
    [$user, $company, , $priceList, , $unit] = storeScenario();
    [$item] = publishedItem($company, $unit);
    priceFor($company, $item, $priceList, 10);
    todayExchangeRate($company, $user, 'USD', 36.5);

    getJson(route('api.store.settings'), storeKeyHeaders())
        ->assertOk()
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.secondary_currency', 'VES')
        ->assertJsonPath('data.exchange_rate', '36.50000000');
});

test('the listing can be filtered by category, search and featured', function () {
    [, $company, , , , $unit] = storeScenario();
    $tools = Category::factory()->create(['company_id' => $company->id, 'name' => 'Herramientas']);
    $paint = Category::factory()->create(['company_id' => $company->id, 'name' => 'Pinturas']);

    [, $drill] = publishedItem($company, $unit, ['category_id' => $tools->id, 'name' => 'Taladro', 'sku' => 'TAL-500'], ['title' => 'Taladro percutor', 'is_featured' => 'yes']);
    [, $hammer] = publishedItem($company, $unit, ['category_id' => $tools->id, 'name' => 'Martillo'], ['title' => 'Martillo de goma']);
    [, $enamel] = publishedItem($company, $unit, ['category_id' => $paint->id, 'name' => 'Esmalte'], ['title' => 'Esmalte blanco']);

    $slugs = fn (array $query): array => collect(getJson(route('api.store.products.index', $query), storeKeyHeaders())->json('data'))->pluck('slug')->sort()->values()->all();

    expect($slugs(['category' => $tools->id]))->toBe(collect([$drill->slug, $hammer->slug])->sort()->values()->all())
        ->and($slugs(['q' => 'TAL-500']))->toBe([$drill->slug])
        ->and($slugs(['q' => 'goma']))->toBe([$hammer->slug])
        ->and($slugs(['featured' => 'yes']))->toBe([$drill->slug])
        ->and($slugs(['q' => 'Esmalte']))->toBe([$enamel->slug]);
});

test('the listing can be sorted by order, name, price and newest', function () {
    [, $company, , $priceList, , $unit] = storeScenario();

    [$a, $pubA] = publishedItem($company, $unit, [], ['title' => 'Zeta', 'order' => 2, 'published_at' => now()->subDays(3)]);
    [$b, $pubB] = publishedItem($company, $unit, [], ['title' => 'Alfa', 'order' => 1, 'published_at' => now()->subDay()]);
    [$c, $pubC] = publishedItem($company, $unit, [], ['title' => 'Mu', 'order' => 3, 'published_at' => now()]);

    priceFor($company, $a, $priceList, 30);
    priceFor($company, $b, $priceList, 50);
    priceFor($company, $c, $priceList, 10);

    $slugs = fn (string $sort): array => collect(getJson(route('api.store.products.index', ['sort' => $sort]), storeKeyHeaders())->json('data'))->pluck('slug')->all();

    expect($slugs('order'))->toBe([$pubB->slug, $pubA->slug, $pubC->slug])
        ->and($slugs('name'))->toBe([$pubB->slug, $pubC->slug, $pubA->slug])
        ->and($slugs('price'))->toBe([$pubC->slug, $pubA->slug, $pubB->slug])
        ->and($slugs('newest'))->toBe([$pubC->slug, $pubB->slug, $pubA->slug]);
});

test('the listing is paginated with a per_page cap of 60', function () {
    [, $company, , , , $unit] = storeScenario();

    foreach (range(1, 3) as $i) {
        publishedItem($company, $unit);
    }

    $response = getJson(route('api.store.products.index', ['per_page' => 2, 'page' => 2]), storeKeyHeaders())->assertOk();

    expect($response->json('meta'))->toBe(['total' => 3, 'page' => 2, 'per_page' => 2, 'last_page' => 2])
        ->and($response->json('data'))->toHaveCount(1);

    expect(getJson(route('api.store.products.index', ['per_page' => 500]), storeKeyHeaders())->json('meta.per_page'))->toBe(60);
});

test('categories only include those with a visible publication and count them', function () {
    [, $company, , , , $unit] = storeScenario();
    $tools = Category::factory()->create(['company_id' => $company->id, 'name' => 'Herramientas']);
    $paint = Category::factory()->create(['company_id' => $company->id, 'name' => 'Pinturas']);
    Category::factory()->create(['company_id' => $company->id, 'name' => 'Vacía']);

    publishedItem($company, $unit, ['category_id' => $tools->id]);
    publishedItem($company, $unit, ['category_id' => $tools->id]);
    publishedItem($company, $unit, ['category_id' => $paint->id], ['status' => 'inactive']);

    $categories = getJson(route('api.store.categories'), storeKeyHeaders())->assertOk()->json('data');

    expect($categories)->toBe([['id' => $tools->id, 'name' => 'Herramientas', 'count' => 2]]);
});

test('the detail by slug returns the full product and 404 when not visible', function () {
    [, $company, , $priceList, , $unit] = storeScenario();
    $category = Category::factory()->create(['company_id' => $company->id, 'name' => 'Herramientas']);
    [$item, $storeItem] = publishedItem($company, $unit, ['category_id' => $category->id, 'sku' => 'TAL-500'], ['title' => 'Taladro', 'description' => 'Texto']);
    \App\Modules\Store\Models\StoreItemImage::factory()->create(['store_item_id' => $storeItem->id, 'alt_text' => 'Frente']);
    \App\Modules\Store\Models\StoreItemImage::factory()->inactive()->create(['store_item_id' => $storeItem->id]);
    priceFor($company, $item, $priceList, 45);
    [, $hidden] = publishedItem($company, $unit, [], ['status' => 'inactive']);

    $product = getJson(route('api.store.products.show', ['slug' => $storeItem->slug]), storeKeyHeaders())
        ->assertOk()
        ->json('data');

    expect($product['title'])->toBe('Taladro')
        ->and($product['sku'])->toBe('TAL-500')
        ->and($product['description'])->toBe('Texto')
        ->and($product['category'])->toBe(['id' => $category->id, 'name' => 'Herramientas'])
        ->and($product['price']['amount'])->toBe('45.00')
        ->and($product['images'])->toHaveCount(1)
        ->and($product['images'][0]['alt'])->toBe('Frente')
        ->and($product['images'][0]['url'])->toStartWith(config('app.url'));

    getJson(route('api.store.products.show', ['slug' => $hidden->slug]), storeKeyHeaders())->assertNotFound();
    getJson(route('api.store.products.show', ['slug' => 'no-existe']), storeKeyHeaders())->assertNotFound();
});

test('a linked buyer with its own client list sees that list prices', function () {
    [, $company, , $storeList, , $unit] = storeScenario();
    $clientList = PriceList::factory()->create(['company_id' => $company->id]);
    [$item, $storeItem] = publishedItem($company, $unit);
    priceFor($company, $item, $storeList, 45);
    priceFor($company, $item, $clientList, 40);

    $client = \App\Modules\Client\Models\Client::factory()->create(['company_id' => $company->id, 'price_list_id' => $clientList->id]);
    $customer = \App\Modules\Store\Models\StoreCustomer::factory()->linkedTo($client->id)->create(['company_id' => $company->id]);
    $token = $customer->createToken('store', [\App\Modules\Store\Models\StoreCustomer::TOKEN_ABILITY])->plainTextToken;

    expect(getJson(route('api.store.products.show', ['slug' => $storeItem->slug]), storeKeyHeaders())
        ->json('data.price.amount'))->toBe('45.00');

    expect(getJson(route('api.store.products.show', ['slug' => $storeItem->slug]), [
        ...storeKeyHeaders(),
        'Authorization' => "Bearer {$token}",
    ])->json('data.price.amount'))->toBe('40.00');
});
