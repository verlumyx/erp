<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('the exchange rate show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $rate = ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.show', ['company' => $company->id, 'id' => $rate->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('exchange-rates/show')
        ->where('exchangeRate.id', $rate->id)
        ->where('exchangeRate.currency', 'USD')
        ->where('exchangeRate.rate_date', '2026-08-15')
    );
});

test('the exchange rate edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $rate = ExchangeRate::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.edit', ['company' => $company->id, 'id' => $rate->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('exchange-rates/edit')
        ->where('exchangeRate.id', $rate->id)
    );
});

test('showing a missing exchange rate throws a not found exception', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('exchange-rates.show', ['company' => $company->id, 'id' => (string) Str::uuid7()]));
})->throws(ExchangeRateNotFoundException::class);

test('an exchange rate from another company is not visible', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = ExchangeRate::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withoutExceptionHandling()
        ->get(route('exchange-rates.show', ['company' => $company->id, 'id' => $foreign->id]));
})->throws(ExchangeRateNotFoundException::class);
