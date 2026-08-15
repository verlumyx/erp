<?php

declare(strict_types=1);

use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\AccountRenewal;
use App\Modules\Service\Models\Service;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: \App\Modules\User\Models\User, 1: \App\Modules\Company\Models\Company, 2: \App\Modules\Service\Models\Service}
 */
function makeRenewContext(): array
{
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create([
        'company_id' => $company->id,
        'max_profiles' => 3,
    ]);

    return [$user, $company, $service];
}

test('creating an account records an initial purchase movement', function () {
    [$user, $company, $service] = makeRenewContext();

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.store', ['company' => $company->id]), [
            'id' => $id,
            'service_id' => $service->id,
            'email' => 'netflix@test.com',
            'password' => 'secret',
            'cost' => 15.00,
            'purchase_date' => '2026-06-01',
            'next_renewal' => '2026-07-01',
        ])
        ->assertSessionHasNoErrors();

    $renewals = AccountRenewal::where('account_id', $id)->get();

    expect($renewals)->toHaveCount(1);
    expect($renewals->first()->type)->toBe('purchase');
    expect($renewals->first()->amount)->toBe('15.00');
    expect($renewals->first()->period_start->toDateString())->toBe('2026-06-01');
    expect($renewals->first()->period_end->toDateString())->toBe('2026-07-01');
    expect($renewals->first()->company_id)->toBe($company->id);
});

test('renewing an account records a renewal movement and advances next_renewal', function () {
    [$user, $company, $service] = makeRenewContext();

    $account = Account::factory()->forService($service)->create([
        'cost' => 10.00,
        'purchase_date' => '2026-06-01',
        'next_renewal' => '2026-07-01',
    ]);

    $renewalId = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.renew', ['company' => $company->id, 'id' => $account->id]), [
            'id' => $renewalId,
            'amount' => 12.50,
            'next_renewal' => '2026-08-01',
            'notes' => 'Pago mensual',
        ])
        ->assertRedirect(route('accounts.show', ['company' => $company->id, 'id' => $account->id]))
        ->assertSessionHasNoErrors();

    $account->refresh();
    expect($account->next_renewal->toDateString())->toBe('2026-08-01');

    $renewal = AccountRenewal::find($renewalId);
    expect($renewal)->not->toBeNull();
    expect($renewal->type)->toBe('renewal');
    expect($renewal->amount)->toBe('12.50');
    expect($renewal->period_start->toDateString())->toBe('2026-07-01');
    expect($renewal->period_end->toDateString())->toBe('2026-08-01');
    expect($renewal->created_by)->toBe((string) $user->id);
});

test('the new renewal date must be after the current next_renewal', function () {
    [$user, $company, $service] = makeRenewContext();

    $account = Account::factory()->forService($service)->create([
        'next_renewal' => '2026-07-01',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.renew', ['company' => $company->id, 'id' => $account->id]), [
            'id' => (string) Str::uuid7(),
            'amount' => 12.50,
            'next_renewal' => '2026-07-01',
        ])
        ->assertSessionHasErrors('next_renewal');

    expect(AccountRenewal::where('account_id', $account->id)->where('type', 'renewal')->count())->toBe(0);
});

test('a user without the renew permission cannot renew', function () {
    [$user, $company, $service] = makeRenewContext();
    assignRoleWithPermissions($user, $company, ['accounts.show']);

    $account = Account::factory()->forService($service)->create([
        'next_renewal' => '2026-07-01',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('accounts.renew', ['company' => $company->id, 'id' => $account->id]), [
            'id' => (string) Str::uuid7(),
            'amount' => 12.50,
            'next_renewal' => '2026-08-01',
        ])
        ->assertForbidden();
});

test('the shared inertia props expose the user permissions for the renew button', function () {
    [$user, $company, $service] = makeRenewContext();
    assignRoleWithPermissions($user, $company, ['accounts.show', 'accounts.renew']);

    $account = Account::factory()->forService($service)->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.show', ['company' => $company->id, 'id' => $account->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('accounts.renew'))
        );
});

test('a user without the renew permission does not get it in the shared props', function () {
    [$user, $company, $service] = makeRenewContext();
    assignRoleWithPermissions($user, $company, ['accounts.list', 'accounts.show']);

    $account = Account::factory()->forService($service)->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.show', ['company' => $company->id, 'id' => $account->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.permissions', fn ($permissions) => ! collect($permissions)->contains('accounts.renew'))
        );
});

test('the show endpoint returns the renewals history', function () {
    [$user, $company, $service] = makeRenewContext();

    $account = Account::factory()->forService($service)->create([
        'next_renewal' => '2026-07-01',
    ]);

    AccountRenewal::factory()->forAccount($account)->purchase()->create([
        'period_end' => '2026-07-01',
        'paid_at' => '2026-06-01',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('accounts.show', ['company' => $company->id, 'id' => $account->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('accounts/show')
            ->has('account.renewals', 1)
        );
});
