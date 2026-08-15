<?php

declare(strict_types=1);

use App\Modules\Role\Models\Role;

use function Pest\Laravel\actingAs;

test('roles can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    Role::create(['company_id' => $company->id, 'name' => 'Editor', 'status' => 'active', 'permission_type' => 'custom']);
    Role::create(['company_id' => $company->id, 'name' => 'Viewer', 'status' => 'active', 'permission_type' => 'custom']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.index', ['company' => $company->id, 'name' => 'Editor']))
        ->assertInertia(fn ($page) => $page
            ->has('roles', 1)
            ->where('roles.0.name', 'Editor')
        );
});

test('roles can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    Role::create(['company_id' => $company->id, 'name' => 'Inactive Role', 'status' => 'inactive', 'permission_type' => 'custom']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.index', ['company' => $company->id, 'status' => 'inactive']))
        ->assertInertia(fn ($page) => $page
            ->has('roles', 1)
            ->where('roles.0.name', 'Inactive Role')
        );
});

test('roles can be filtered by description', function () {
    [$user, $company] = createUserWithCompany();

    Role::create(['company_id' => $company->id, 'name' => 'Alpha', 'status' => 'active', 'permission_type' => 'custom', 'description' => 'handles billing']);
    Role::create(['company_id' => $company->id, 'name' => 'Beta', 'status' => 'active', 'permission_type' => 'custom', 'description' => 'handles support']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.index', ['company' => $company->id, 'description' => 'billing']))
        ->assertInertia(fn ($page) => $page
            ->has('roles', 1)
            ->where('roles.0.name', 'Alpha')
        );
});

test('role field filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    Role::create(['company_id' => $company->id, 'name' => 'Support', 'status' => 'active', 'permission_type' => 'custom', 'description' => 'tickets']);
    Role::create(['company_id' => $company->id, 'name' => 'Helpdesk', 'status' => 'active', 'permission_type' => 'custom', 'description' => 'tickets']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.index', ['company' => $company->id, 'name' => 'Support', 'description' => 'tickets']))
        ->assertInertia(fn ($page) => $page
            ->has('roles', 1)
            ->where('roles.0.name', 'Support')
        );
});
