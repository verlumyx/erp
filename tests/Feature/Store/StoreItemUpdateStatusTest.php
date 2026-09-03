<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Store\Models\StoreItem;

use function Pest\Laravel\actingAs;

test('a publication can be hidden and shown again', function () {
    [$user, $company] = itemScenario();
    $item = Item::factory()->create(['company_id' => $company->id]);
    $storeItem = StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $item->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update-status', ['company' => $company->id, 'id' => $storeItem->id]), [
            'status' => 'inactive',
        ])->assertSessionHasNoErrors();

    expect($storeItem->fresh()->status)->toBe('inactive');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update-status', ['company' => $company->id, 'id' => $storeItem->id]), [
            'status' => 'active',
        ])->assertSessionHasNoErrors();

    expect($storeItem->fresh()->status)->toBe('active');
});

test('published_at is filled the first time the publication becomes active and never overwritten', function () {
    [$user, $company] = itemScenario();
    $item = Item::factory()->create(['company_id' => $company->id]);
    $storeItem = StoreItem::factory()->inactive()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'published_at' => null,
    ]);

    expect($storeItem->published_at)->toBeNull();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update-status', ['company' => $company->id, 'id' => $storeItem->id]), [
            'status' => 'active',
        ])->assertSessionHasNoErrors();

    $firstPublished = $storeItem->fresh()->published_at;
    expect($firstPublished)->not->toBeNull();

    $this->travel(2)->days();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update-status', ['company' => $company->id, 'id' => $storeItem->id]), [
            'status' => 'inactive',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update-status', ['company' => $company->id, 'id' => $storeItem->id]), [
            'status' => 'active',
        ]);

    expect($storeItem->fresh()->published_at->equalTo($firstPublished))->toBeTrue();
});

test('the status must be active or inactive', function () {
    [$user, $company] = itemScenario();
    $item = Item::factory()->create(['company_id' => $company->id]);
    $storeItem = StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $item->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('store-items.update-status', ['company' => $company->id, 'id' => $storeItem->id]), [
            'status' => 'cancelled',
        ])->assertSessionHasErrors(['status']);
});
