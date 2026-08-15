<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withoutVite();
        $this->withoutMiddleware(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create an authenticated-ready user that belongs to a freshly created company.
 *
 * The user is granted a role with permission_type "all", so by default it has
 * full access. Tests that need a restricted user should grant explicit
 * permissions instead (see assignRoleWithPermissions).
 *
 * @return array{0: \App\Modules\User\Models\User, 1: \App\Modules\Company\Models\Company}
 */
function createUserWithCompany(): array
{
    $user = \App\Modules\User\Models\User::factory()->create();

    $company = \App\Modules\Company\Models\Company::create([
        'name' => 'Company '.uniqid(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $role = \App\Modules\Role\Models\Role::create([
        'company_id' => $company->id,
        'name' => 'Acceso Total '.uniqid(),
        'status' => 'active',
        'permission_type' => 'all',
    ]);

    $user->companies()->attach($company->id, [
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    return [$user, $company];
}

/**
 * Assign a custom role to the user within the given company, granting exactly
 * the provided permission action strings (permission_type "custom").
 *
 * @param  array<int, string>  $permissions
 */
function assignRoleWithPermissions(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    array $permissions = [],
): \App\Modules\Role\Models\Role {
    $role = \App\Modules\Role\Models\Role::create([
        'company_id' => $company->id,
        'name' => 'Custom '.uniqid(),
        'status' => 'active',
        'permission_type' => 'custom',
    ]);

    foreach ($permissions as $permission) {
        \App\Modules\Role\Models\RolePermission::create([
            'role_id' => $role->id,
            'permission' => $permission,
        ]);
    }

    \App\Modules\Shared\Models\UserCompany::where('user_id', $user->id)
        ->where('company_id', $company->id)
        ->update(['role_id' => $role->id]);

    return $role;
}

/**
 * Build a full Sales context: an authenticated user/company plus a service with
 * `$maxProfiles`, an active client, an account with `$maxProfiles` available
 * profiles, and a "profile" capacity plan. Tests can derive other plans/profiles.
 *
 * @return array{
 *     user: \App\Modules\User\Models\User,
 *     company: \App\Modules\Company\Models\Company,
 *     service: \App\Modules\Service\Models\Service,
 *     client: \App\Modules\Client\Models\Client,
 *     account: \App\Modules\Account\Models\Account,
 *     profiles: \Illuminate\Support\Collection<int, \App\Modules\Account\Models\Profile>,
 *     plan: \App\Modules\Plan\Models\Plan
 * }
 */
function makeSaleContext(int $maxProfiles = 4): array
{
    [$user, $company] = createUserWithCompany();

    $service = \App\Modules\Service\Models\Service::factory()->create([
        'company_id' => $company->id,
        'max_profiles' => $maxProfiles,
    ]);

    $client = \App\Modules\Client\Models\Client::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $account = \App\Modules\Account\Models\Account::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
    ]);

    $profiles = collect(range(1, $maxProfiles))->map(fn (int $number) => \App\Modules\Account\Models\Profile::factory()->create([
        'account_id' => $account->id,
        'number' => $number,
        'status' => 'available',
    ]))->values();

    $plan = \App\Modules\Plan\Models\Plan::factory()->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
        'capacity' => 'profile',
        'duration_days' => 30,
        'sale_price' => 50.00,
    ]);

    return compact('user', 'company', 'service', 'client', 'account', 'profiles', 'plan');
}

/**
 * Crea una venta activa persistida directamente (sin pasar por el endpoint),
 * con su profile ocupado, para los escenarios de renovación/cancelación/reembolso.
 *
 * @param  array<string, mixed>  $ctx
 */
function persistSale(array $ctx, string $status = 'active', ?string $endDate = null): \App\Modules\Sale\Models\Sale
{
    $profile = $ctx['profiles']->first();
    $profile->update(['status' => 'occupied']);

    $sale = \App\Modules\Sale\Models\Sale::factory()->forPlan($ctx['plan'])->create([
        'company_id' => $ctx['company']->id,
        'client_id' => $ctx['client']->id,
        'agent_id' => $ctx['user']->id,
        'status' => $status,
        'end_date' => $endDate ?? now()->addDays(10)->toDateString(),
    ]);

    \App\Modules\Sale\Models\SaleProfile::create([
        'id' => (string) \Illuminate\Support\Str::uuid7(),
        'sale_id' => $sale->id,
        'profile_id' => $profile->id,
    ]);

    return $sale;
}
