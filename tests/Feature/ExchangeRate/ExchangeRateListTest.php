<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Models\ExchangeRate;

use function Pest\Laravel\actingAs;

test('the exchange rates index renders with rates', function () {
    [$user, $company] = createUserWithCompany();

    ExchangeRate::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('exchange-rates/index')
        ->has('exchangeRates', 3)
        ->where('meta.total', 3)
    );
});

test('exchange rates can be filtered by currency', function () {
    [$user, $company] = createUserWithCompany();

    $target = ExchangeRate::factory()->create(['company_id' => $company->id, 'currency' => 'EUR']);
    ExchangeRate::factory()->create(['company_id' => $company->id, 'currency' => 'USD']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', ['company' => $company->id, 'currency' => 'EUR']));

    $response->assertInertia(fn ($page) => $page
        ->has('exchangeRates', 1)
        ->where('exchangeRates.0.id', $target->id)
    );
});

test('exchange rates can be filtered by date', function () {
    [$user, $company] = createUserWithCompany();

    $target = ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'rate_date' => '2026-08-15',
        'currency' => 'USD',
    ]);
    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'rate_date' => '2026-08-14',
        'currency' => 'USD',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', ['company' => $company->id, 'rate_date' => '2026-08-15']));

    $response->assertInertia(fn ($page) => $page
        ->has('exchangeRates', 1)
        ->where('exchangeRates.0.id', $target->id)
    );
});

test('exchange rates can be filtered by type', function () {
    [$user, $company] = createUserWithCompany();

    $target = ExchangeRate::factory()->manual()->create(['company_id' => $company->id]);
    ExchangeRate::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', ['company' => $company->id, 'type' => 'manual']));

    $response->assertInertia(fn ($page) => $page
        ->has('exchangeRates', 1)
        ->where('exchangeRates.0.id', $target->id)
    );
});

test('exchange rates can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = ExchangeRate::factory()->create(['company_id' => $company->id, 'code' => 'TAS000042']);
    ExchangeRate::factory()->create(['company_id' => $company->id, 'code' => 'TAS000099']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', ['company' => $company->id, 'code' => 'TAS000042']));

    $response->assertInertia(fn ($page) => $page
        ->has('exchangeRates', 1)
        ->where('exchangeRates.0.id', $target->id)
    );
});

test('exchange rates can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    ExchangeRate::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    ExchangeRate::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('exchangeRates', 1));
});

test('exchange rate filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    $target = ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'type' => 'legal',
    ]);
    ExchangeRate::factory()->manual()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', [
            'company' => $company->id,
            'currency' => 'USD',
            'type' => 'legal',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('exchangeRates', 1)
        ->where('exchangeRates.0.id', $target->id)
    );
});

test('the index only shows exchange rates from the active company', function () {
    [$user, $company] = createUserWithCompany();

    ExchangeRate::factory()->count(2)->create(['company_id' => $company->id]);
    ExchangeRate::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('exchangeRates', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list exchange rates', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('exchange-rates.index', ['company' => $company->id]));

    $response->assertForbidden();
});
