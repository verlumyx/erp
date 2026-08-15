<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Service\Models\Service;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: \App\Modules\User\Models\User, 1: \App\Modules\Company\Models\Company, 2: \App\Modules\Service\Models\Service}
 */
function makeAccountContext(int $maxProfiles = 4): array
{
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create([
        'company_id' => $company->id,
        'max_profiles' => $maxProfiles,
    ]);

    return [$user, $company, $service];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function accountPayload(Service $service, array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::uuid7(),
        'service_id' => $service->id,
        'email' => 'netflix.account@test.com',
        'password' => 'super-secret',
        'cost' => 12.50,
        'purchase_date' => now()->toDateString(),
        'next_renewal' => now()->addDays(30)->toDateString(),
    ], $overrides);
}

test('a account can be created and auto-generates one profile per service slot', function () {
    [$user, $company, $service] = makeAccountContext(maxProfiles: 5);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, ['id' => $id]));

    $response->assertRedirect(route('accounts.show', ['company' => $company->id, 'id' => $id]));
    $response->assertSessionHasNoErrors();

    $account = Account::find($id);
    expect($account)->not->toBeNull();
    expect($account->code)->toBe('ACC000001');
    expect($account->status)->toBe('active');
    expect($account->company_id)->toBe($company->id);

    $profiles = $account->profiles()->orderBy('number')->get();
    expect($profiles)->toHaveCount(5);
    expect($profiles->pluck('number')->all())->toBe([1, 2, 3, 4, 5]);
    expect($profiles->pluck('status')->unique()->all())->toBe(['available']);
    expect($profiles->pluck('pin')->filter()->all())->toBe([]);
});

test('the password is stored encrypted, never in plain text', function () {
    [$user, $company, $service] = makeAccountContext();

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, [
            'id' => $id,
            'password' => 'plain-text-pass',
        ]));

    $raw = \Illuminate\Support\Facades\DB::table('app_accounts')->where('id', $id)->value('password_encrypted');
    expect($raw)->not->toBe('plain-text-pass');

    // El cast `encrypted` lo descifra al leer desde el modelo.
    expect(Account::find($id)->password_encrypted)->toBe('plain-text-pass');
});

test('optional profiles array pre-fills PINs by number', function () {
    [$user, $company, $service] = makeAccountContext(maxProfiles: 4);

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, [
            'id' => $id,
            'profiles' => [
                ['number' => 1, 'pin' => '1234'],
                ['number' => 3, 'pin' => '9999'],
            ],
        ]))
        ->assertSessionHasNoErrors();

    $profiles = Account::find($id)->profiles()->orderBy('number')->get()->keyBy('number');
    expect($profiles[1]->pin)->toBe('1234');
    expect($profiles[2]->pin)->toBeNull();
    expect($profiles[3]->pin)->toBe('9999');
    expect($profiles[4]->pin)->toBeNull();
});

test('the code auto-increments per company', function () {
    [$user, $company, $service] = makeAccountContext();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, [
            'id' => $first, 'email' => 'a@test.com',
        ]));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, [
            'id' => $second, 'email' => 'b@test.com',
        ]));

    expect(Account::find($first)->code)->toBe('ACC000001');
    expect(Account::find($second)->code)->toBe('ACC000002');
});

test('a profile number outside the service range is rejected', function () {
    [$user, $company, $service] = makeAccountContext(maxProfiles: 2);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, [
            'profiles' => [['number' => 3, 'pin' => '1234']],
        ]));

    $response->assertSessionHasErrors('profiles.0.number');
});

test('the same email cannot repeat within the same service', function () {
    [$user, $company, $service] = makeAccountContext();

    Account::factory()->forService($service)->create(['email' => 'dupe@test.com']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, [
            'email' => 'dupe@test.com',
        ]));

    $response->assertSessionHasErrors('email');
});

test('the same email can exist on a different service', function () {
    [$user, $company, $service] = makeAccountContext();
    $otherService = Service::factory()->create(['company_id' => $company->id, 'max_profiles' => 3]);

    Account::factory()->forService($otherService)->create(['email' => 'shared@test.com']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, [
            'email' => 'shared@test.com',
        ]));

    $response->assertSessionHasNoErrors();
});

test('next_renewal must not be before purchase_date', function () {
    [$user, $company, $service] = makeAccountContext();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service, [
            'purchase_date' => '2026-06-10',
            'next_renewal' => '2026-06-01',
        ]));

    $response->assertSessionHasErrors('next_renewal');
});

test('a user without permission cannot create a account', function () {
    [$user, $company, $service] = makeAccountContext();
    assignRoleWithPermissions($user, $company, ['accounts.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), accountPayload($service));

    $response->assertForbidden();
});
