<?php

declare(strict_types=1);

use App\Modules\PriceList\Exceptions\PriceListNotFoundException;
use App\Modules\PriceList\Models\PriceList;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the price list show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->create([
        'company_id' => $company->id,
        'name' => 'Visible',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.show', ['company' => $company->id, 'id' => $priceList->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('price-lists/show')
        ->where('priceList.id', $priceList->id)
        ->where('priceList.name', 'Visible')
        ->where('priceList.code', $priceList->code)
    );
});

test('the price list edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $priceList = PriceList::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('price-lists.edit', ['company' => $company->id, 'id' => $priceList->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('price-lists/edit')
        ->where('priceList.id', $priceList->id)
    );
});

test('showing a missing price list throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('price-lists.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(PriceListNotFoundException::class);

test('a price list from another company is not reachable', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = PriceList::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('price-lists.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(PriceListNotFoundException::class);
