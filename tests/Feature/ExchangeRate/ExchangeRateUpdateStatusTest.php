<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Models\ExchangeRate;

use function Pest\Laravel\actingAs;

test('an exchange rate status can be changed', function () {
    [$user, $company] = createUserWithCompany();

    $rate = ExchangeRate::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('exchange-rates.update-status', ['company' => $company->id, 'id' => $rate->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('exchange-rates.index', ['company' => $company->id]));
    $response->assertSessionHas('success');
    expect($rate->fresh()->status)->toBe('inactive');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $rate = ExchangeRate::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('exchange-rates.update-status', ['company' => $company->id, 'id' => $rate->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
    expect($rate->fresh()->status)->toBe('active');
});

test('a user without permission cannot change the exchange rate status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['exchange-rates.list']);

    $rate = ExchangeRate::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('exchange-rates.update-status', ['company' => $company->id, 'id' => $rate->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
