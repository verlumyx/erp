<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\Profile;
use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: \App\Modules\User\Models\User, 1: \App\Modules\Company\Models\Company, 2: \App\Modules\Account\Models\Account}
 */
function makeAccountWithProfiles(): array
{
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id, 'max_profiles' => 4]);
    $account = Account::factory()->forService($service)->create(['email' => 'orig@test.com']);

    foreach (range(1, 4) as $number) {
        Profile::factory()->create(['account_id' => $account->id, 'number' => $number, 'status' => 'available']);
    }

    return [$user, $company, $account];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function updatePayload(array $overrides = []): array
{
    return array_merge([
        'email' => 'updated@test.com',
        'cost' => 20.00,
        'purchase_date' => now()->toDateString(),
        'next_renewal' => now()->addDays(60)->toDateString(),
        'status' => 'active',
    ], $overrides);
}

test('a account header and its profiles update in a single call', function () {
    [$user, $company, $account] = makeAccountWithProfiles();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('accounts.update', ['company' => $company->id, 'id' => $account->id]), updatePayload([
            'status' => 'maintenance',
            'profiles' => [
                ['number' => 1, 'pin' => '1111', 'status' => 'occupied'],
                ['number' => 2, 'status' => 'maintenance', 'notes' => 'en revisión'],
            ],
        ]));

    $response->assertRedirect(route('accounts.show', ['company' => $company->id, 'id' => $account->id]));
    $response->assertSessionHasNoErrors();

    $account->refresh();
    expect($account->email)->toBe('updated@test.com');
    expect($account->status)->toBe('maintenance');

    $profiles = $account->profiles()->orderBy('number')->get()->keyBy('number');
    expect($profiles[1]->pin)->toBe('1111');
    expect($profiles[1]->status)->toBe('occupied');
    expect($profiles[2]->status)->toBe('maintenance');
    expect($profiles[2]->notes)->toBe('en revisión');
});

test('updating a profile that does not belong to the account is rejected', function () {
    [$user, $company, $account] = makeAccountWithProfiles();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('accounts.update', ['company' => $company->id, 'id' => $account->id]), updatePayload([
            'profiles' => [['number' => 99, 'pin' => '0000']],
        ]));

    $response->assertSessionHasErrors('profiles.0.number');
});

test('an invalid profile status value is rejected', function () {
    [$user, $company, $account] = makeAccountWithProfiles();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('accounts.update', ['company' => $company->id, 'id' => $account->id]), updatePayload([
            'profiles' => [['number' => 1, 'status' => 'cancelled']],
        ]));

    $response->assertSessionHasErrors('profiles.0.status');
});

test('a user without update permission cannot update a account', function () {
    [$user, $company, $account] = makeAccountWithProfiles();
    assignRoleWithPermissions($user, $company, ['accounts.show']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('accounts.update', ['company' => $company->id, 'id' => $account->id]), updatePayload());

    $response->assertForbidden();
});
