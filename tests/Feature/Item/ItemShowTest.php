<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PriceList\Models\PriceList;

use function Pest\Laravel\actingAs;

test('an item detail is rendered with its units and prices', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    $priceList = PriceList::factory()->create(['company_id' => $company->id]);

    ItemUnit::factory()->base()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'measurement_unit_id' => $unit->id,
    ]);

    ItemPrice::factory()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'price_list_id' => $priceList->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.show', ['company' => $company->id, 'id' => $item->id]));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('items/show')
            ->where('item.id', $item->id)
            ->has('item.units', 1)
            ->where('item.units.0.measurement_unit_name', $unit->name)
            ->has('item.prices', 1)
            ->where('item.prices.0.price_list_name', $priceList->name)
    );
});

test('an item from another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();

    $foreignItem = Item::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.show', ['company' => $company->id, 'id' => $foreignItem->id]))
        ->assertNotFound();
});

test('the edit form is rendered with the catalogs it needs', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.edit', ['company' => $company->id, 'id' => $item->id]));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('items/edit')
            ->where('item.id', $item->id)
            ->has('options.measurementUnits')
    );
});

test('the create form only offers catalogs of the active company', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->create(['company_id' => $company->id]);
    MeasurementUnit::factory()->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.create', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page->component('items/create')->has('options.measurementUnits', 1)
    );
});

test('a user without permission cannot see an item', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['items.list']);

    $item = Item::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('items.show', ['company' => $company->id, 'id' => $item->id]))
        ->assertForbidden();
});
