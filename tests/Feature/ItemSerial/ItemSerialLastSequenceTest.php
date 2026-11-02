<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemSerial\Models\ItemSerial;

use function Pest\Laravel\actingAs;

test('returns default sequence when item has no serials', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'sku' => 'LAP01',
        'type' => 'serialized',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('item-serials.last-sequence', [
            'company' => $company->id,
            'item_id' => $item->id,
        ]));

    $response->assertOk();
    $response->assertJson([
        'item_id' => $item->id,
        'last_serial_number' => null,
        'prefix' => 'LAP01-',
        'next_number' => 1,
        'pad_length' => 4,
        'suggested_serial' => 'LAP01-0001',
        'existing_serials' => [],
    ]);
});

test('returns next sequence following the last serial generated for the item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create([
        'company_id' => $company->id,
        'sku' => 'PROD',
        'type' => 'serialized',
    ]);

    ItemSerial::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'serial_number' => 'PROD-0001',
        'created_at' => now()->subMinutes(10),
    ]);

    ItemSerial::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'serial_number' => 'PROD-0002',
        'created_at' => now()->subMinutes(5),
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('item-serials.last-sequence', [
            'company' => $company->id,
            'item_id' => $item->id,
        ]));

    $response->assertOk();
    $response->assertJson([
        'item_id' => $item->id,
        'last_serial_number' => 'PROD-0002',
        'prefix' => 'PROD-',
        'next_number' => 3,
        'pad_length' => 4,
        'suggested_serial' => 'PROD-0003',
        'existing_serials' => ['PROD-0001', 'PROD-0002'],
    ]);
});

test('does not mix serials of different items', function () {
    [$user, $company] = createUserWithCompany();

    $itemA = Item::factory()->create([
        'company_id' => $company->id,
        'sku' => 'ITEMA',
        'type' => 'serialized',
    ]);

    $itemB = Item::factory()->create([
        'company_id' => $company->id,
        'sku' => 'ITEMB',
        'type' => 'serialized',
    ]);

    ItemSerial::factory()->create([
        'company_id' => $company->id,
        'item_id' => $itemB->id,
        'serial_number' => 'SN-0099',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('item-serials.last-sequence', [
            'company' => $company->id,
            'item_id' => $itemA->id,
        ]));

    $response->assertOk();
    // itemA has no serials, so next is 1, not 100!
    $response->assertJson([
        'item_id' => $itemA->id,
        'last_serial_number' => null,
        'next_number' => 1,
        'suggested_serial' => 'ITEMA-0001',
    ]);
});
