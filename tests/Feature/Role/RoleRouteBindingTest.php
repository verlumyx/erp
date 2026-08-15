<?php

declare(strict_types=1);

use App\Modules\Role\Models\Role;

use function Pest\Laravel\actingAs;

function createRoleForCompany(string $companyId): Role
{
    return Role::create([
        'company_id' => $companyId,
        'name' => 'Role '.uniqid(),
        'status' => 'active',
        'permission_type' => 'custom',
        'description' => 'A role for testing route binding',
    ]);
}

test('roles.edit binds the role id and not the company id', function () {
    [$user, $company] = createUserWithCompany();
    $role = createRoleForCompany($company->id);

    expect($company->id)->not->toBe($role->id);

    $response = actingAs($user)->get(route('roles.edit', [
        'company' => $company->id,
        'id' => $role->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('roles/edit')
        ->where('role.id', $role->id)
    );
});

test('roles.show binds the role id and not the company id', function () {
    [$user, $company] = createUserWithCompany();
    $role = createRoleForCompany($company->id);

    $response = actingAs($user)->get(route('roles.show', [
        'company' => $company->id,
        'id' => $role->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('roles/show')
        ->where('role.id', $role->id)
    );
});
