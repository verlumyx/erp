<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Role\Models\Role;
use App\Modules\Shared\Models\UserCompany;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * Attach an extra company to an existing user with the given company/pivot status.
 *
 * @return array{0: \App\Modules\Company\Models\Company}
 */
function attachCompany(
    \App\Modules\User\Models\User $user,
    string $companyStatus = 'active',
    string $pivotStatus = 'active',
    bool $isDefault = false,
): array {
    $company = Company::create([
        'name' => 'Company '.uniqid(),
        'status' => $companyStatus,
        'created_by' => $user->id,
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'name' => 'Acceso Total '.uniqid(),
        'status' => 'active',
        'permission_type' => 'all',
    ]);

    $user->companies()->attach($company->id, [
        'id' => (string) Str::uuid(),
        'role_id' => $role->id,
        'status' => $pivotStatus,
        'is_default' => $isDefault,
    ]);

    return [$company];
}

test('accessing an inactive company redirects to an active company dashboard with an error flash', function () {
    [$user, $activeCompany] = createUserWithCompany();
    [$inactiveCompany] = attachCompany($user, companyStatus: 'inactive');

    $response = actingAs($user)->get("/{$inactiveCompany->id}/dashboard");

    $response->assertRedirect("/{$activeCompany->id}/dashboard");
    $response->assertSessionHas('error');
});

test('accessing a company where the user membership is inactive redirects to an active company', function () {
    [$user, $activeCompany] = createUserWithCompany();
    [$company] = attachCompany($user, companyStatus: 'active', pivotStatus: 'inactive');

    $response = actingAs($user)->get("/{$company->id}/dashboard");

    $response->assertRedirect("/{$activeCompany->id}/dashboard");
    $response->assertSessionHas('error');
});

test('accessing an active company the user belongs to still works', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)->get("/{$company->id}/dashboard");

    $response->assertOk();
});

test('a user that does not belong to the company is forbidden', function () {
    [$user] = createUserWithCompany();
    [$otherUser, $otherCompany] = createUserWithCompany();

    $response = actingAs($user)->get("/{$otherCompany->id}/dashboard");

    $response->assertForbidden();
});

test('when the user has no active companies it aborts with 403 instead of looping', function () {
    [$user, $onlyCompany] = createUserWithCompany();
    UserCompany::where('user_id', $user->id)->update(['status' => 'inactive']);

    $response = actingAs($user)->get("/{$onlyCompany->id}/dashboard");

    $response->assertForbidden();
});

test('a system owner can access an inactive company', function () {
    [$user, $activeCompany] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);
    [$inactiveCompany] = attachCompany($user, companyStatus: 'inactive');

    $response = actingAs($user)->get("/{$inactiveCompany->id}/dashboard");

    $response->assertOk();
});
