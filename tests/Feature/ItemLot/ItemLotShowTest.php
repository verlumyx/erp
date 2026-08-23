<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Exceptions\ItemLotNotFoundException;
use App\Modules\ItemLot\Models\ItemLot;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a lot of the company can be seen with its item', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id, 'name' => 'Vacuna']);
    $lot = ItemLot::factory()->expiring('2027-03-01')->create([
        'item_id' => $item->id,
        'company_id' => $company->id,
        'lot_number' => 'L-VER',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-lots.show', ['company' => $company->id, 'id' => $lot->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('item-lots/show')
        ->where('lot.lot_number', 'L-VER')
        ->where('lot.item_name', 'Vacuna')
        ->where('lot.expires_at', '2027-03-01')
    );
});

test('the edit page renders with the lot', function () {
    [$user, $company] = createUserWithCompany();

    $item = Item::factory()->create(['company_id' => $company->id]);
    $lot = ItemLot::factory()->create(['item_id' => $item->id, 'company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('item-lots.edit', ['company' => $company->id, 'id' => $lot->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('item-lots/edit')
            ->where('lot.id', $lot->id)
        );
});

test('showing a missing lot throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('item-lots.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(ItemLotNotFoundException::class);

test('a lot from another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = ItemLot::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('item-lots.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(ItemLotNotFoundException::class);
