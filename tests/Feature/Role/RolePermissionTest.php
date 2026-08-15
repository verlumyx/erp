<?php

declare(strict_types=1);

use App\Modules\Role\Models\Role;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

function createRoleFor(string $companyId): Role
{
    return Role::create([
        'company_id' => $companyId,
        'name' => 'Target '.uniqid(),
        'status' => 'active',
        'permission_type' => 'custom',
    ]);
}

test('a user without roles.list cannot open the roles index', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.index', ['company' => $company->id]))
        ->assertForbidden();
});

test('a user without roles.create cannot open the create view', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['roles.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.create', ['company' => $company->id]))
        ->assertForbidden();
});

test('a user without roles.show cannot open the show view', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['roles.list']);
    $role = createRoleFor($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.show', ['company' => $company->id, 'id' => $role->id]))
        ->assertForbidden();
});

test('a user without roles.update cannot open the edit view', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['roles.list']);
    $role = createRoleFor($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.edit', ['company' => $company->id, 'id' => $role->id]))
        ->assertForbidden();
});

test('a user without roles.create cannot store a role', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['roles.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('roles.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid(),
            'name' => 'New role',
            'permission_type' => 'custom',
            'permissions' => [],
        ])
        ->assertForbidden();

    expect(Role::where('name', 'New role')->exists())->toBeFalse();
});

test('a user without roles.update cannot update a role', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['roles.list']);
    $role = createRoleFor($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('roles.update', ['company' => $company->id, 'id' => $role->id]), [
            'name' => 'Renamed',
            'permission_type' => 'custom',
            'permissions' => [],
        ])
        ->assertForbidden();
});

test('a user without roles.update-status cannot change a role status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['roles.list']);
    $role = createRoleFor($company->id);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('roles.update-status', ['company' => $company->id, 'id' => $role->id]), [
            'status' => 'inactive',
        ])
        ->assertForbidden();

    expect($role->fresh()->status)->toBe('active');
});

test('an Inertia request to a forbidden view redirects back with a flash error', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $version = (new App\Http\Middleware\HandleInertiaRequests)->version(request());

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => (string) $version])
        ->get(route('roles.create', ['company' => $company->id]));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('a direct request to a forbidden view renders the custom 403 page', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.index', ['company' => $company->id]))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('errors/403'));
});

test('granting the matching permissions allows access to each action', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['roles.list', 'roles.create', 'roles.show', 'roles.update']);
    $role = createRoleFor($company->id);

    $session = ['current_company_id' => $company->id];

    actingAs($user)->withSession($session)->get(route('roles.index', ['company' => $company->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('roles.create', ['company' => $company->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('roles.show', ['company' => $company->id, 'id' => $role->id]))->assertOk();
    actingAs($user)->withSession($session)->get(route('roles.edit', ['company' => $company->id, 'id' => $role->id]))->assertOk();
});
