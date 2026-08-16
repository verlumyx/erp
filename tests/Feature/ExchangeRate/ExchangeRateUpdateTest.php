<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Models\ExchangeRate;

use function Pest\Laravel\actingAs;

test('an exchange rate can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $rate = ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'rate' => '36.5',
        'type' => 'legal',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('exchange-rates.update', ['company' => $company->id, 'id' => $rate->id]), [
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '37.25',
            'type' => 'legal',
            'source' => 'Banco Central',
            'description' => 'Corrección',
        ]);

    $response->assertRedirect(route('exchange-rates.show', ['company' => $company->id, 'id' => $rate->id]));
    $response->assertSessionHasNoErrors();

    $rate->refresh();
    expect((float) $rate->rate)->toBe(37.25);
    expect($rate->source)->toBe('Banco Central');
    expect($rate->description)->toBe('Corrección');
});

test('an exchange rate can be moved to another date', function () {
    [$user, $company] = createUserWithCompany();

    $rate = ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'type' => 'legal',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('exchange-rates.update', ['company' => $company->id, 'id' => $rate->id]), [
            'currency' => 'USD',
            'rate_date' => '2026-08-16',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertSessionHasNoErrors();
    expect($rate->fresh()->rate_date->toDateString())->toBe('2026-08-16');
});

test('the updated rate must not collide with another rate for the same currency, date and type', function () {
    [$user, $company] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'type' => 'legal',
    ]);
    $rate = ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-14',
        'type' => 'legal',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('exchange-rates.update', ['company' => $company->id, 'id' => $rate->id]), [
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertSessionHasErrors('rate_date');
    expect($rate->fresh()->rate_date->toDateString())->toBe('2026-08-14');
});

test('updating a rate keeps its own natural key valid', function () {
    [$user, $company] = createUserWithCompany();

    $rate = ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'type' => 'legal',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('exchange-rates.update', ['company' => $company->id, 'id' => $rate->id]), [
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '40',
            'type' => 'legal',
        ]);

    $response->assertSessionHasNoErrors();
    expect((float) $rate->fresh()->rate)->toBe(40.0);
});

test('a user without permission cannot update an exchange rate', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['exchange-rates.list']);

    $rate = ExchangeRate::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('exchange-rates.update', ['company' => $company->id, 'id' => $rate->id]), [
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertForbidden();
});
