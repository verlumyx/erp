<?php

declare(strict_types=1);

use App\Modules\Category\Models\Category;
use App\Modules\Item\Models\Item;

use function Pest\Laravel\actingAs;

test('the item list is rendered with its items', function () {
    [$user, $company] = createUserWithCompany();

    Item::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('items/index')
            ->has('items', 3)
            ->where('meta.total', 3)
            ->has('options.categories')
            ->has('options.measurementUnits')
            ->has('options.priceLists')
    );
});

test('the list only shows items of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Item::factory()->create(['company_id' => $company->id]);
    Item::factory()->count(2)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page->has('items', 1));
});

test('items can be filtered by sku', function () {
    [$user, $company] = createUserWithCompany();

    Item::factory()->create(['company_id' => $company->id, 'sku' => 'MART-001']);
    Item::factory()->create(['company_id' => $company->id, 'sku' => 'DEST-002']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.index', ['company' => $company->id, 'sku' => 'MART']));

    $response->assertInertia(
        fn ($page) => $page->has('items', 1)->where('items.0.sku', 'MART-001')
    );
});

test('items can be filtered by type and status', function () {
    [$user, $company] = createUserWithCompany();

    Item::factory()->create(['company_id' => $company->id, 'type' => 'inventoried']);
    Item::factory()->service()->create(['company_id' => $company->id]);
    Item::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.index', ['company' => $company->id, 'type' => 'service']))
        ->assertInertia(fn ($page) => $page->has('items', 1));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.index', ['company' => $company->id, 'status' => 'inactive']))
        ->assertInertia(fn ($page) => $page->has('items', 1));
});

test('items can be filtered by category', function () {
    [$user, $company] = createUserWithCompany();

    $category = Category::factory()->create(['company_id' => $company->id]);
    Item::factory()->create(['company_id' => $company->id, 'category_id' => $category->id]);
    Item::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.index', ['company' => $company->id, 'category_id' => $category->id]));

    $response->assertInertia(fn ($page) => $page->has('items', 1));
});

test('a user without permission cannot list items', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.index', ['company' => $company->id]))
        ->assertForbidden();
});
