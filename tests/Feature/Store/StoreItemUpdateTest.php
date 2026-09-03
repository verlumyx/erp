<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Store\Models\StoreItem;

use function Pest\Laravel\actingAs;

test('the texts, featured flag and order can be edited', function () {
    [$user, $company] = itemScenario();
    $item = Item::factory()->create(['company_id' => $company->id]);
    $storeItem = StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $item->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update', ['company' => $company->id, 'id' => $storeItem->id]), [
            'title' => 'Nuevo título',
            'summary' => 'Frase corta',
            'description' => 'Descripción **larga**',
            'is_featured' => 'yes',
            'order' => 7,
        ])
        ->assertRedirect(route('store-items.edit', ['company' => $company->id, 'id' => $storeItem->id]))
        ->assertSessionHasNoErrors();

    $storeItem->refresh();
    expect($storeItem->title)->toBe('Nuevo título')
        ->and($storeItem->summary)->toBe('Frase corta')
        ->and($storeItem->description)->toBe('Descripción **larga**')
        ->and($storeItem->is_featured)->toBe('yes')
        ->and($storeItem->order)->toBe(7);
});

test('the slug is kept when it is not sent and changed with a suffix on collision', function () {
    [$user, $company] = itemScenario();
    $first = Item::factory()->create(['company_id' => $company->id]);
    $second = Item::factory()->create(['company_id' => $company->id]);

    StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $first->id, 'slug' => 'taladro']);
    $storeItem = StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $second->id, 'slug' => 'esmeril']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update', ['company' => $company->id, 'id' => $storeItem->id]), [
            'title' => 'Esmeril angular',
            'is_featured' => 'no',
            'order' => 0,
        ])->assertSessionHasNoErrors();

    expect($storeItem->fresh()->slug)->toBe('esmeril');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update', ['company' => $company->id, 'id' => $storeItem->id]), [
            'title' => 'Esmeril angular',
            'slug' => 'taladro',
            'is_featured' => 'no',
            'order' => 0,
        ])->assertSessionHasNoErrors();

    expect($storeItem->fresh()->slug)->toBe('taladro-2');
});

test('editing its own slug does not count as a collision', function () {
    [$user, $company] = itemScenario();
    $item = Item::factory()->create(['company_id' => $company->id]);
    $storeItem = StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $item->id, 'slug' => 'lija']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update', ['company' => $company->id, 'id' => $storeItem->id]), [
            'title' => 'Lija',
            'slug' => 'lija',
            'is_featured' => 'no',
            'order' => 0,
        ])->assertSessionHasNoErrors();

    expect($storeItem->fresh()->slug)->toBe('lija');
});

test('the edit screen shows the item price and availability read-only', function () {
    [$user, $company, $settings, $priceList, $warehouse, $unit] = storeScenario();
    [$item, $storeItem] = publishedItem($company, $unit);

    \App\Modules\Item\Models\ItemPrice::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'price_list_id' => $priceList->id,
        'price' => 45,
        'currency' => 'USD',
    ]);

    \App\Modules\ItemStock\Models\ItemStock::factory()->withBalance(12)->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('store-items.edit', ['company' => $company->id, 'id' => $storeItem->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('store/items/edit', false)
            ->where('store_item.id', $storeItem->id)
            ->where('store_item.price.amount', '45.00')
            ->where('store_item.price.currency', 'USD')
            ->where('store_item.availability.in_stock', 'yes')
            ->where('store_item.item.sku', $item->sku));
});
