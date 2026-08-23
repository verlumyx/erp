<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Models\ItemLot;

use function Pest\Laravel\actingAs;

test('a lot can be blocked and released again', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-lots.update-status', ['company' => $company->id, 'id' => $lot->id]), [
            'status' => 'blocked',
        ]);

    $response->assertRedirect(route('item-lots.index', ['company' => $company->id]));
    expect($lot->refresh()->status)->toBe('blocked');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-lots.update-status', ['company' => $company->id, 'id' => $lot->id]), [
            'status' => 'active',
        ]);

    expect($lot->refresh()->status)->toBe('active');
});

test('a lot can be marked as expired', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-lots.update-status', ['company' => $company->id, 'id' => $lot->id]), [
            'status' => 'expired',
        ])->assertSessionHasNoErrors();

    expect($lot->refresh()->status)->toBe('expired');
});

test('an unknown status is rejected', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-lots.update-status', ['company' => $company->id, 'id' => $lot->id]), [
            'status' => 'inactive',
        ])->assertSessionHasErrors('status');

    expect($lot->refresh()->status)->toBe('active');
});
