<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;

use function Pest\Laravel\actingAs;

test('the list only shows lots of the active company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $otherItem = Item::factory()->create(['company_id' => $otherCompany->id]);

    ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'MINE']);
    ItemLot::factory()->create(['item_id' => $otherItem->id, 'company_id' => $otherCompany->id, 'lot_number' => 'THEIRS']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-lots.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('item-lots/index')
        ->has('lots', 1)
        ->where('lots.0.lot_number', 'MINE')
        ->where('meta.total', 1)
    );
});

test('the list is ordered FEFO: what expires first comes first, undated last', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);

    ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'SIN-FECHA']);
    ItemLot::factory()->expiring('2027-01-01')->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'TARDE']);
    ItemLot::factory()->expiring('2026-09-01')->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'PRONTO']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-lots.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->where('lots.0.lot_number', 'PRONTO')
        ->where('lots.1.lot_number', 'TARDE')
        ->where('lots.2.lot_number', 'SIN-FECHA')
    );
});

test('the list can be filtered by item and by status', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $otherItem = Item::factory()->create(['company_id' => $company->id]);

    ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'A']);
    ItemLot::factory()->blocked()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'B']);
    ItemLot::factory()->create(['item_id' => $otherItem->id, 'company_id' => $company->id, 'lot_number' => 'C']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-lots.index', ['company' => $company->id, 'item_id' => $item->id]))
        ->assertInertia(fn ($page) => $page->has('lots', 2));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-lots.index', ['company' => $company->id, 'status' => 'blocked']))
        ->assertInertia(fn ($page) => $page->has('lots', 1)->where('lots.0.lot_number', 'B'));
});

test('the lookup returns only active lots of the requested item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $otherItem = Item::factory()->create(['company_id' => $company->id]);

    ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'OK']);
    ItemLot::factory()->blocked()->create(['item_id' => $item->id, 'company_id' => $company->id, 'lot_number' => 'BLOQUEADO']);
    ItemLot::factory()->create(['item_id' => $otherItem->id, 'company_id' => $company->id, 'lot_number' => 'OTRO']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('item-lots.lookup', ['company' => $company->id, 'item_id' => $item->id]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.meta.lot_number'))->toBe('OK');
    expect($response->json('has_more'))->toBeFalse();
});

test('the lookup hydrates an already chosen lot regardless of its status', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $blocked = ItemLot::factory()->blocked()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'lot_number' => 'BLOQUEADO',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('item-lots.lookup', ['company' => $company->id, 'ids' => $blocked->id]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.value'))->toBe($blocked->id);
});
