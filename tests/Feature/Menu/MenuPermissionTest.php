<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\GetActiveMenusService;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Models\RolePermission;
use App\Modules\Shared\Models\UserCompany;

test('menu items are filtered by the current role permissions (stored as action strings)', function () {
    [$user, $company] = createUserWithCompany();

    $role = Role::create([
        'company_id' => $company->id,
        'name' => 'RRHH '.uniqid(),
        'status' => 'active',
        'permission_type' => 'custom',
    ]);

    // Permissions are keyed by their action string, matching the menu's `permission`.
    RolePermission::create(['role_id' => $role->id, 'permission' => 'users.list']);

    UserCompany::where('user_id', $user->id)
        ->where('company_id', $company->id)
        ->update(['role_id' => $role->id]);

    Menu::create(['title' => 'Usuarios', 'url' => '/users', 'permission' => 'users.list', 'icon' => 'users', 'section' => 'footer', 'is_active' => true, 'order' => 1]);
    Menu::create(['title' => 'Roles', 'url' => '/roles', 'permission' => 'roles.list', 'icon' => 'shield', 'section' => 'footer', 'is_active' => true, 'order' => 2]);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    $titles = collect($menus['footerNavItems'])->pluck('title')->all();

    expect($titles)->toContain('Usuarios')   // role HAS users.list
        ->and($titles)->not->toContain('Roles'); // role LACKS roles.list
});

test('a menu with the reserved system_owner permission is hidden from non owners even with a full access role', function () {
    [$user, $company] = createUserWithCompany(); // rol permission_type "all", pero NO dueño

    Menu::create(['title' => 'Empresas', 'url' => '/companies', 'permission' => 'system_owner', 'icon' => 'Building2', 'section' => 'footer', 'is_active' => true, 'order' => 3]);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $titles = collect($menus['footerNavItems'])->pluck('title')->all();

    expect($titles)->not->toContain('Empresas');
});

test('a menu with the reserved system_owner permission is visible only to the system owner', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    Menu::create(['title' => 'Empresas', 'url' => '/companies', 'permission' => 'system_owner', 'icon' => 'Building2', 'section' => 'footer', 'is_active' => true, 'order' => 3]);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $titles = collect($menus['footerNavItems'])->pluck('title')->all();

    expect($titles)->toContain('Empresas');
});

test('a role with permission_type all sees every menu item', function () {
    [$user, $company] = createUserWithCompany();

    $role = Role::create([
        'company_id' => $company->id,
        'name' => 'Admin '.uniqid(),
        'status' => 'active',
        'permission_type' => 'all',
    ]);

    UserCompany::where('user_id', $user->id)
        ->where('company_id', $company->id)
        ->update(['role_id' => $role->id]);

    Menu::create(['title' => 'Usuarios', 'url' => '/users', 'permission' => 'users.list', 'icon' => 'users', 'section' => 'footer', 'is_active' => true, 'order' => 1]);
    Menu::create(['title' => 'Roles', 'url' => '/roles', 'permission' => 'roles.list', 'icon' => 'shield', 'section' => 'footer', 'is_active' => true, 'order' => 2]);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $titles = collect($menus['footerNavItems'])->pluck('title')->all();

    expect($titles)->toContain('Usuarios')->toContain('Roles');
});
