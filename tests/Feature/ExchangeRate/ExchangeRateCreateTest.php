<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Models\ExchangeRate;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('an exchange rate can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => $id,
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
            'source' => 'Banco Central',
            'description' => 'Tasa oficial del día',
        ]);

    $response->assertRedirect(route('exchange-rates.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $rate = ExchangeRate::find($id);
    expect($rate)->not->toBeNull();
    expect($rate->currency)->toBe('USD');
    expect($rate->rate_date->toDateString())->toBe('2026-08-15');
    expect((float) $rate->rate)->toBe(36.5);
    expect($rate->type)->toBe('legal');
    expect($rate->source)->toBe('Banco Central');
    expect($rate->status)->toBe('active');
    expect($rate->created_by)->toBe($user->id);
    expect($rate->company_id)->toBe($company->id);
    expect($rate->code)->toBe('TAS000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => $first,
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => $second,
            'currency' => 'EUR',
            'rate_date' => '2026-08-15',
            'rate' => '40.1',
            'type' => 'legal',
        ]);

    expect(ExchangeRate::find($first)->code)->toBe('TAS000001');
    expect(ExchangeRate::find($second)->code)->toBe('TAS000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('exchange-rates.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('exchange-rates.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    expect(ExchangeRate::find($idA)->code)->toBe('TAS000001');
    expect(ExchangeRate::find($idB)->code)->toBe('TAS000001');
});

test('reloading the same currency, date and type updates the existing rate', function () {
    [$user, $company] = createUserWithCompany();

    $existing = ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'type' => 'legal',
        'rate' => '36.5',
        'code' => 'TAS000001',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '37.25',
            'type' => 'legal',
            'source' => 'Boletín corregido',
        ]);

    $response->assertSessionHasNoErrors();

    expect(ExchangeRate::where('company_id', $company->id)->count())->toBe(1);
    $existing->refresh();
    expect((float) $existing->rate)->toBe(37.25);
    expect($existing->source)->toBe('Boletín corregido');
    expect($existing->code)->toBe('TAS000001');
});

test('reloading a deactivated rate reactivates it', function () {
    [$user, $company] = createUserWithCompany();

    $existing = ExchangeRate::factory()->inactive()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'type' => 'legal',
        'rate' => '99',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $existing->refresh();
    expect($existing->status)->toBe('active');
    expect((float) $existing->rate)->toBe(36.5);
});

test('the same currency and date can coexist with a different type', function () {
    [$user, $company] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'type' => 'legal',
    ]);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => $id,
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '38',
            'type' => 'manual',
        ]);

    $response->assertSessionHasNoErrors();
    expect(ExchangeRate::where('company_id', $company->id)->count())->toBe(2);
    expect(ExchangeRate::find($id)->type)->toBe('manual');
});

test('the currency must be USD or EUR', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'ARS',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertSessionHasErrors('currency');
});

test('the rate date is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'USD',
            'rate_date' => '',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertSessionHasErrors('rate_date');
});

test('the rate must be a non negative number', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '-1',
            'type' => 'legal',
        ]);

    $response->assertSessionHasErrors('rate');
});

test('the type must be legal or manual', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'oficial',
        ]);

    $response->assertSessionHasErrors('type');
});

test('another company can load the same currency, date and type', function () {
    [$user, $company] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'currency' => 'USD',
        'rate_date' => '2026-08-15',
        'type' => 'legal',
    ]);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => $id,
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertSessionHasNoErrors();
    expect(ExchangeRate::find($id))->not->toBeNull();
});

test('a user without permission cannot create an exchange rate', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['exchange-rates.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('exchange-rates.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'currency' => 'USD',
            'rate_date' => '2026-08-15',
            'rate' => '36.5',
            'type' => 'legal',
        ]);

    $response->assertForbidden();
});
