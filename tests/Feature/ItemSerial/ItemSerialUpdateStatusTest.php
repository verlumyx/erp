<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemSerial\Models\ItemSerial;

use function Pest\Laravel\actingAs;

test('marking a serial as sold stamps the exit date', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $serial = ItemSerial::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update-status', ['company' => $company->id, 'id' => $serial->id]), [
            'status' => 'sold',
        ]);

    $response->assertRedirect(route('item-serials.index', ['company' => $company->id]));

    $serial->refresh();
    expect($serial->status)->toBe('sold');
    expect($serial->sold_at)->not->toBeNull();
});

test('a returned serial clears the exit date', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $serial = ItemSerial::factory()->sold()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update-status', ['company' => $company->id, 'id' => $serial->id]), [
            'status' => 'returned',
        ])->assertSessionHasNoErrors();

    $serial->refresh();
    expect($serial->status)->toBe('returned');
    expect($serial->sold_at)->toBeNull();
});

test('re-marking a sold serial keeps the original exit date', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $soldAt = now()->subDays(5)->startOfSecond();
    $serial = ItemSerial::factory()->sold()->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'sold_at' => $soldAt,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update-status', ['company' => $company->id, 'id' => $serial->id]), [
            'status' => 'sold',
        ])->assertSessionHasNoErrors();

    expect($serial->refresh()->sold_at->toDateTimeString())->toBe($soldAt->toDateTimeString());
});

test('an unknown serial status is rejected', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'type' => 'serialized']);
    $serial = ItemSerial::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('item-serials.update-status', ['company' => $company->id, 'id' => $serial->id]), [
            'status' => 'inactive',
        ])->assertSessionHasErrors('status');
});
