<?php

declare(strict_types=1);

use App\Modules\Role\Models\Role;

use function Pest\Laravel\actingAs;

test('editing a role preserves its company_id', function () {
    [$user, $company] = createUserWithCompany();

    $role = Role::create([
        'company_id' => $company->id,
        'name' => 'Manager '.uniqid(),
        'status' => 'active',
        'permission_type' => 'custom',
        'description' => 'Original',
    ]);

    // The edit form does not send company_id (only name/description/permission_type/permissions).
    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('roles.update', ['company' => $company->id, 'id' => $role->id]), [
            'name' => 'Manager updated',
            'permission_type' => 'custom',
            'description' => 'Updated description',
            'permissions' => [],
        ]);

    $response->assertRedirect(route('roles.show', ['company' => $company->id, 'id' => $role->id]));
    $response->assertSessionHasNoErrors();

    $role->refresh();
    expect($role->company_id)->toBe($company->id);
    expect($role->name)->toBe('Manager updated');
});

test('the Administrador role cannot be edited', function () {
    [$user, $company] = createUserWithCompany();

    $admin = Role::create([
        'company_id' => $company->id,
        'name' => 'Administrador',
        'status' => 'active',
        'permission_type' => 'all',
        'description' => 'Rol administrador de la empresa',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('roles.update', ['company' => $company->id, 'id' => $admin->id]), [
            'name' => 'Hacked',
            'permission_type' => 'custom',
            'description' => 'changed',
            'permissions' => [],
        ]);

    $response->assertSessionHasErrors('name');

    $admin->refresh();
    expect($admin->name)->toBe('Administrador');
    expect($admin->permission_type)->toBe('all');
});

test('the Administrador role status cannot be changed', function () {
    [$user, $company] = createUserWithCompany();

    $admin = Role::create([
        'company_id' => $company->id,
        'name' => 'Administrador',
        'status' => 'active',
        'permission_type' => 'all',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('roles.update-status', ['company' => $company->id, 'id' => $admin->id]), [
            'status' => 'inactive',
        ]);

    $response->assertSessionHasErrors('status');
    expect($admin->fresh()->status)->toBe('active');
});
