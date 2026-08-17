<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;

use function Pest\Laravel\actingAs;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function configurationPayload(array $overrides = []): array
{
    return [
        'base_currency' => 'EUR',
        'secondary_currency' => 'VES',
        'rate_type' => 'legal',
        'allows_rate_override' => 'yes',
        'amount_decimals' => 2,
        'price_decimals' => 6,
        ...$overrides,
    ];
}

test('the configuration can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('configuration.update', ['company' => $company->id]), configurationPayload([
            'rate_type' => 'manual',
            'allows_rate_override' => 'no',
            'amount_decimals' => 4,
        ]));

    $response->assertRedirect(route('configuration.edit', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $configuration = Configuration::query()->where('company_id', $company->id)->first();

    expect($configuration->base_currency)->toBe('EUR')
        ->and($configuration->secondary_currency)->toBe('VES')
        ->and($configuration->rate_type)->toBe('manual')
        ->and($configuration->allows_rate_override)->toBe('no')
        ->and($configuration->amount_decimals)->toBe(4);
});

test('a company can work only in bolivares', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('configuration.update', ['company' => $company->id]), configurationPayload([
            'base_currency' => 'VES',
            'secondary_currency' => 'VES',
        ]))
        ->assertSessionHasNoErrors();

    $configuration = Configuration::query()->where('company_id', $company->id)->first();

    expect($configuration->usesDualCurrency())->toBeFalse();
});

test('the second currency can be dropped altogether', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('configuration.update', ['company' => $company->id]), configurationPayload([
            'secondary_currency' => null,
        ]))
        ->assertSessionHasNoErrors();

    $configuration = Configuration::query()->where('company_id', $company->id)->first();

    expect($configuration->secondary_currency)->toBeNull()
        ->and($configuration->usesDualCurrency())->toBeFalse();
});

test('the main currency must exist in the catalog', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('configuration.update', ['company' => $company->id]), configurationPayload([
            'base_currency' => 'XYZ',
        ]))
        ->assertSessionHasErrors(['base_currency' => 'La moneda seleccionada no es válida.']);
});

test('the rate type is limited to legal or manual', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('configuration.update', ['company' => $company->id]), configurationPayload([
            'rate_type' => 'parallel',
        ]))
        ->assertSessionHasErrors('rate_type');
});

test('the decimals stay within their bounds', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('configuration.update', ['company' => $company->id]), configurationPayload([
            'amount_decimals' => 9,
        ]))
        ->assertSessionHasErrors('amount_decimals');
});

test('updating is denied without the update permission', function () {
    [$user, $company] = createUserWithCompany();

    assignRoleWithPermissions($user, $company, ['configuration.show']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('configuration.update', ['company' => $company->id]), configurationPayload())
        ->assertForbidden();

    expect(Configuration::query()->where('company_id', $company->id)->first()->base_currency)
        ->toBe('USD');
});
