<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemPrice;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PriceList\Models\PriceList;

use function Pest\Laravel\actingAs;

test('the lookup endpoint returns items as select options', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'code' => 'ART000001',
        'name' => 'Martillo',
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonPath('data.0.value', $item->id);
    $response->assertJsonPath('data.0.label', 'ART000001 — Martillo');
    $response->assertJsonPath('data.0.meta.sku', $item->sku);
    $response->assertJsonPath('has_more', false);
});

test('the lookup endpoint searches by name, code, sku and barcode', function (string $term) {
    [$user, $company] = createUserWithCompany();

    Item::factory()->create([
        'company_id' => $company->id,
        'code' => 'ART000009',
        'name' => 'Taladro percutor',
        'sku' => 'TAL-001',
        'barcode' => '7591234567890',
        'status' => 'active',
    ]);
    Item::factory()->create([
        'company_id' => $company->id,
        'code' => 'ART000010',
        'name' => 'Destornillador',
        'sku' => 'DES-002',
        'barcode' => '7599876543210',
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', ['company' => $company->id, 'q' => $term]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.label', 'ART000009 — Taladro percutor');
})->with(['percutor', 'ART000009', 'TAL-001', '7591234567890']);

test('the lookup endpoint only returns active items of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Item::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Item::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);
    Item::factory()->create(['status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

test('the lookup endpoint paginates and reports whether more pages remain', function () {
    [$user, $company] = createUserWithCompany();

    Item::factory()->count(5)->create(['company_id' => $company->id, 'status' => 'active']);

    $first = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', [
            'company' => $company->id,
            'per_page' => 2,
            'page' => 1,
        ]));

    $first->assertOk();
    $first->assertJsonCount(2, 'data');
    $first->assertJsonPath('has_more', true);

    $last = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', [
            'company' => $company->id,
            'per_page' => 2,
            'page' => 3,
        ]));

    $last->assertOk();
    $last->assertJsonCount(1, 'data');
    $last->assertJsonPath('has_more', false);
});

test('the lookup endpoint hydrates the already selected ids', function () {
    [$user, $company] = createUserWithCompany();

    $selected = Item::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Item::factory()->count(3)->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', [
            'company' => $company->id,
            'ids' => $selected->id,
        ]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.value', $selected->id);
});

test('hydrating by id also resolves an item deactivated after being chosen', function () {
    [$user, $company] = createUserWithCompany();

    $chosen = Item::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', ['company' => $company->id, 'ids' => $chosen->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.value', $chosen->id);
});

test('the lookup endpoint caps how many options a single page can ask for', function () {
    [$user, $company] = createUserWithCompany();

    Item::factory()->count(55)->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', ['company' => $company->id, 'per_page' => 500]));

    $response->assertOk();
    $response->assertJsonCount(50, 'data');
});

test('each option carries the units and prices the order line needs', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id, 'name' => 'Caja']);
    $priceList = PriceList::factory()->create(['company_id' => $company->id]);

    ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);
    ItemPrice::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'price_list_id' => $priceList->id,
        'price' => 25.5,
        'currency' => 'USD',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonPath('data.0.meta.units.0.measurement_unit_id', $unit->id);
    $response->assertJsonPath('data.0.meta.units.0.name', 'Caja');
    $response->assertJsonPath('data.0.meta.units.0.is_base', 'yes');
    $response->assertJsonPath('data.0.meta.prices.0.price_list_id', $priceList->id);
    $response->assertJsonPath('data.0.meta.prices.0.price', '25.500000');
    $response->assertJsonPath('data.0.meta.prices.0.currency', 'USD');
});

test('inactive units and prices stay out of the option', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'status' => 'inactive',
    ]);
    ItemPrice::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'price_list_id' => PriceList::factory()->create(['company_id' => $company->id])->id,
        'status' => 'inactive',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonCount(0, 'data.0.meta.units');
    $response->assertJsonCount(0, 'data.0.meta.prices');
});

test('the lookup endpoint narrows the catalog to what can be sold or purchased', function (string $filter) {
    [$user, $company] = createUserWithCompany();

    Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'is_sellable' => 'yes',
        'is_purchasable' => 'yes',
    ]);
    Item::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'is_sellable' => 'no',
        'is_purchasable' => 'no',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('items.lookup', ['company' => $company->id, $filter => 'yes']));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
})->with(['is_sellable', 'is_purchasable']);
