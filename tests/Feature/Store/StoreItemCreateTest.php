<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Store\Models\StoreItem;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('an item can be published with title and description preloaded from the item', function () {
    [$user, $company, $unit] = itemScenario();

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'name' => 'Taladro percutor 500W',
        'description' => 'Ideal para mampostería liviana.',
    ]);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.store', ['company' => $company->id]), [
            'id' => $id,
            'item_id' => $item->id,
            'is_featured' => 'yes',
            'order' => 2,
        ]);

    $response->assertRedirect(route('store-items.edit', ['company' => $company->id, 'id' => $id]));
    $response->assertSessionHasNoErrors();

    $storeItem = StoreItem::findOrFail($id);
    expect($storeItem->code)->toBe('PUB000001')
        ->and($storeItem->title)->toBe('Taladro percutor 500W')
        ->and($storeItem->description)->toBe('Ideal para mampostería liviana.')
        ->and($storeItem->slug)->toBe('taladro-percutor-500w')
        ->and($storeItem->is_featured)->toBe('yes')
        ->and($storeItem->order)->toBe(2)
        ->and($storeItem->status)->toBe('active')
        ->and($storeItem->published_at)->not->toBeNull()
        ->and($storeItem->created_by)->toBe($user->id)
        ->and($storeItem->company_id)->toBe($company->id);
});

test('the slug is generated from the title and gets a numeric suffix on collision', function () {
    [$user, $company] = itemScenario();

    $first = Item::factory()->create(['company_id' => $company->id, 'name' => 'Martillo']);
    $second = Item::factory()->create(['company_id' => $company->id, 'name' => 'Martillo de goma']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'item_id' => $first->id,
            'title' => 'Martillo',
        ])->assertSessionHasNoErrors();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'item_id' => $second->id,
            'title' => 'Martillo',
        ])->assertSessionHasNoErrors();

    expect(StoreItem::where('item_id', $first->id)->value('slug'))->toBe('martillo')
        ->and(StoreItem::where('item_id', $second->id)->value('slug'))->toBe('martillo-2');
});

test('an item that is not sellable cannot be published', function () {
    [$user, $company] = itemScenario();
    $item = Item::factory()->create(['company_id' => $company->id, 'is_sellable' => 'no']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'item_id' => $item->id,
        ])->assertSessionHasErrors(['item_id']);

    expect(StoreItem::count())->toBe(0);
});

test('an inactive item cannot be published', function () {
    [$user, $company] = itemScenario();
    $item = Item::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'item_id' => $item->id,
        ])->assertSessionHasErrors(['item_id']);
});

test('an item from another company cannot be published', function () {
    [$user, $company] = itemScenario();
    [, $otherCompany] = createUserWithCompany();
    $item = Item::factory()->create(['company_id' => $otherCompany->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'item_id' => $item->id,
        ])->assertSessionHasErrors(['item_id']);
});

test('an item already published cannot be published twice', function () {
    [$user, $company] = itemScenario();
    $item = Item::factory()->create(['company_id' => $company->id]);
    StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $item->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'item_id' => $item->id,
        ])->assertSessionHasErrors(['item_id']);

    expect(StoreItem::where('item_id', $item->id)->count())->toBe(1);
});

test('the lookup only offers sellable active items without a publication', function () {
    [$user, $company] = itemScenario();

    $available = Item::factory()->create(['company_id' => $company->id, 'name' => 'Sierra circular']);
    $published = Item::factory()->create(['company_id' => $company->id, 'name' => 'Sierra caladora']);
    Item::factory()->create(['company_id' => $company->id, 'name' => 'Sierra interna', 'is_sellable' => 'no']);
    StoreItem::factory()->create(['company_id' => $company->id, 'item_id' => $published->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('store-items.lookup', ['company' => $company->id, 'q' => 'Sierra']));

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('value')->all())->toBe([$available->id]);
    expect($response->json('data.0.meta.name'))->toBe('Sierra circular');
});

test('a user without the permission cannot publish', function () {
    [$user, $company] = itemScenario();
    assignRoleWithPermissions($user, $company, ['store-items.list']);
    $item = Item::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('store-items.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'item_id' => $item->id,
        ])->assertForbidden();
});
