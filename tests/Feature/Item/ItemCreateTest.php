<?php

declare(strict_types=1);

use App\Modules\Category\Models\Category;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PriceList\Models\PriceList;

use function Pest\Laravel\actingAs;

test('an item can be created with its base unit', function () {
    [$user, $company, $unit] = itemScenario();

    $payload = itemPayload($unit, ['barcode' => '7501234567890', 'description' => 'Mango de fibra']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('items.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $item = Item::find($payload['id']);
    expect($item)->not->toBeNull();
    expect($item->sku)->toBe('SKU-001');
    expect($item->name)->toBe('Martillo de carpintero');
    expect($item->barcode)->toBe('7501234567890');
    expect($item->type)->toBe('inventoried');
    expect($item->status)->toBe('active');
    expect($item->code)->toBe('ART000001');
    expect($item->company_id)->toBe($company->id);
    expect($item->created_by)->toBe($user->id);

    expect($item->units)->toHaveCount(1);
    expect($item->units->first()->is_base)->toBe('yes');
    expect($item->units->first()->measurement_unit_id)->toBe($unit->id);
    expect($item->units->first()->company_id)->toBe($company->id);
});

test('the code auto-increments per company', function () {
    [$user, $company, $unit] = itemScenario();

    $first = itemPayload($unit, ['sku' => 'SKU-A']);
    $second = itemPayload($unit, ['sku' => 'SKU-B', 'name' => 'Segundo']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), $first);
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), $second);

    expect(Item::find($first['id'])->code)->toBe('ART000001');
    expect(Item::find($second['id'])->code)->toBe('ART000002');
});

test('an item can be created with its units and prices', function () {
    [$user, $company, $unit] = itemScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    $priceList = PriceList::factory()->create(['company_id' => $company->id]);
    $category = Category::factory()->create(['company_id' => $company->id]);

    $payload = itemPayload($unit, [
        'category_id' => $category->id,
        'min_price' => 10,
        'units' => [
            ['measurement_unit_id' => $unit->id, 'is_base' => 'yes', 'conversion_factor' => 1],
            ['measurement_unit_id' => $box->id, 'is_base' => 'no', 'conversion_factor' => 12],
        ],
        'prices' => [
            [
                'price_list_id' => $priceList->id,
                'price' => 25.5,
                'currency' => 'USD',
                'valid_from' => '2026-01-01',
                'valid_to' => null,
            ],
        ],
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), $payload);

    $response->assertSessionHasNoErrors();

    $item = Item::with(['units', 'prices'])->find($payload['id']);
    expect($item->category_id)->toBe($category->id);
    expect($item->units)->toHaveCount(2);
    expect($item->prices)->toHaveCount(1);

    $price = $item->prices->first();
    expect((float) $price->price)->toBe(25.5);
    expect($price->currency)->toBe('USD');
    expect($price->valid_from->format('Y-m-d'))->toBe('2026-01-01');
    expect($price->company_id)->toBe($company->id);
});

test('the base unit is always stored with a conversion factor of one', function () {
    [$user, $company, $unit] = itemScenario();

    $payload = itemPayload($unit, [
        'units' => [
            ['measurement_unit_id' => $unit->id, 'is_base' => 'yes', 'conversion_factor' => 48],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), $payload);

    expect((float) Item::find($payload['id'])->units->first()->conversion_factor)->toBe(1.0);
});

test('the sku is required', function () {
    [$user, $company, $unit] = itemScenario();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit, ['sku' => '']));

    $response->assertSessionHasErrors('sku');
});

test('the sku must be unique within the company', function () {
    [$user, $company, $unit] = itemScenario();

    Item::factory()->create(['company_id' => $company->id, 'sku' => 'SKU-001']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit));

    $response->assertSessionHasErrors('sku');
});

test('another company can reuse the same sku', function () {
    [$user, $company, $unit] = itemScenario();

    Item::factory()->create(['sku' => 'SKU-001']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit));

    $response->assertSessionHasNoErrors();
});

test('the barcode must be unique within the company', function () {
    [$user, $company, $unit] = itemScenario();

    Item::factory()->create(['company_id' => $company->id, 'barcode' => '7501234567890']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit, [
            'barcode' => '7501234567890',
        ]));

    $response->assertSessionHasErrors('barcode');
});

test('the item requires at least one unit', function () {
    [$user, $company, $unit] = itemScenario();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit, ['units' => []]));

    $response->assertSessionHasErrors('units');
});

test('exactly one unit must be marked as base', function () {
    [$user, $company, $unit] = itemScenario();
    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit, [
            'units' => [
                ['measurement_unit_id' => $unit->id, 'is_base' => 'yes', 'conversion_factor' => 1],
                ['measurement_unit_id' => $box->id, 'is_base' => 'yes', 'conversion_factor' => 12],
            ],
        ]));

    $response->assertSessionHasErrors('units');
});

test('an item without a base unit is rejected', function () {
    [$user, $company, $unit] = itemScenario();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit, [
            'units' => [
                ['measurement_unit_id' => $unit->id, 'is_base' => 'no', 'conversion_factor' => 1],
            ],
        ]));

    $response->assertSessionHasErrors('units');
});

test('the same measurement unit cannot be repeated', function () {
    [$user, $company, $unit] = itemScenario();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit, [
            'units' => [
                ['measurement_unit_id' => $unit->id, 'is_base' => 'yes', 'conversion_factor' => 1],
                ['measurement_unit_id' => $unit->id, 'is_base' => 'no', 'conversion_factor' => 12],
            ],
        ]));

    $response->assertSessionHasErrors('units.1.measurement_unit_id');
});

test('a price below the item min_price is rejected', function () {
    [$user, $company, $unit] = itemScenario();
    $priceList = PriceList::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit, [
            'min_price' => 100,
            'prices' => [
                ['price_list_id' => $priceList->id, 'price' => 50, 'currency' => 'USD'],
            ],
        ]));

    $response->assertSessionHasErrors('prices.0.price');
});

test('a measurement unit from another company is rejected', function () {
    [$user, $company] = createUserWithCompany();
    $foreignUnit = MeasurementUnit::factory()->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($foreignUnit));

    $response->assertSessionHasErrors('units.0.measurement_unit_id');
});

test('a user without permission cannot create an item', function () {
    [$user, $company, $unit] = itemScenario();
    assignRoleWithPermissions($user, $company, ['items.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('items.store', ['company' => $company->id]), itemPayload($unit));

    $response->assertForbidden();
});
