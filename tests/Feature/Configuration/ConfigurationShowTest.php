<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;

use function Pest\Laravel\actingAs;

test('the configuration page renders with the company defaults', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('configuration.edit', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('configuration/edit')
        ->where('configuration.company_id', $company->id)
        ->where('configuration.base_currency', 'USD')
        ->where('configuration.secondary_currency', 'VES')
        ->where('configuration.dual_currency', true)
        ->where('configuration.rate_type', 'legal')
        ->where('configuration.allows_rate_override', 'yes')
    );
});

/**
 * Las empresas anteriores a este módulo no tienen fila: la estrenan sola al
 * primer acceso, sin migración de relleno.
 */
test('a company without configuration gets one on first access', function () {
    [$user, $company] = createUserWithCompany();

    Configuration::query()->where('company_id', $company->id)->delete();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('configuration.edit', ['company' => $company->id]));

    $response->assertOk();
    expect(Configuration::query()->where('company_id', $company->id)->count())->toBe(1);
});

test('an amount is shown in a single currency when the company works in bolivares', function () {
    [$user, $company] = createUserWithCompany();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['base_currency' => 'VES', 'secondary_currency' => 'VES']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('configuration.edit', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->where('configuration.dual_currency', false)
    );
});

test('the configuration page is denied without the show permission', function () {
    [$user, $company] = createUserWithCompany();

    assignRoleWithPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('configuration.edit', ['company' => $company->id]))
        ->assertForbidden();
});

test('the configuration of another company is out of reach', function () {
    [$user] = createUserWithCompany();
    [, $other] = createUserWithCompany();

    actingAs($user)
        ->get(route('configuration.edit', ['company' => $other->id]))
        ->assertForbidden();
});
