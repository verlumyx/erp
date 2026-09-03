<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Menu\Models\Menu;
use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;
use App\Modules\Role\Models\Role;
use App\Modules\Shared\Models\CompanyDisabledMenu;
use App\Modules\Shared\Models\UserCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Si la empresa no ve un menú, los permisos de ese módulo no se ofrecen en sus
 * roles ni se anuncian a sus usuarios: un rol no puede dar lo que la empresa
 * no tiene.
 */
function seedPermissionModule(string $name): void
{
    $moduleId = (string) Str::uuid();

    DB::table('app_modules')->insert([
        'id' => $moduleId,
        'name' => $name,
        'label' => ucfirst($name),
        'is_active' => true,
        'order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Permission::create([
        'module_id' => $moduleId,
        'action' => $name.'.list',
        'label' => $name.'.list',
        'is_active' => true,
        'order' => 1,
    ]);
}

/** @return array{group: Menu, leaf: Menu} */
function seedStoreMenu(): array
{
    $group = Menu::factory()->group()->create(['title' => 'Tienda', 'order' => 8]);
    $leaf = Menu::factory()->create([
        'parent_id' => $group->id,
        'title' => 'Publicaciones',
        'url' => '/store-items',
        'permission' => 'store-items.list',
        'order' => 1,
    ]);

    return ['group' => $group, 'leaf' => $leaf];
}

test('the permissions tree and the flat list skip the modules whose menu the company does not see', function () {
    [$user, $company] = createUserWithCompany();
    seedPermissionModule('clients');
    seedPermissionModule('store-items');
    ['leaf' => $leaf] = seedStoreMenu();

    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $leaf->id]);

    $repository = app(PermissionRepositoryInterface::class);

    expect(collect($repository->getAllGroupedByModule($company->id))->pluck('name')->all())
        ->toContain('clients')->not->toContain('store-items')
        ->and($repository->getAllPermissionsFlat($company->id))
        ->toContain('clients.list')->not->toContain('store-items.list');
});

test('without a company, or for another company, nothing is skipped', function () {
    [$user, $company] = createUserWithCompany();
    $otherCompany = Company::factory()->create();
    seedPermissionModule('store-items');
    ['leaf' => $leaf] = seedStoreMenu();

    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $leaf->id]);

    $repository = app(PermissionRepositoryInterface::class);

    expect($repository->getAllPermissionsFlat())->toContain('store-items.list')
        ->and($repository->getAllPermissionsFlat($otherCompany->id))->toContain('store-items.list')
        ->and(collect($repository->getAllGroupedByModule($otherCompany->id))->pluck('name')->all())->toContain('store-items');
});

test('disabling the group itself drags its children modules along', function () {
    [$user, $company] = createUserWithCompany();
    seedPermissionModule('store-items');
    ['group' => $group] = seedStoreMenu();

    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $group->id]);

    expect(app(PermissionRepositoryInterface::class)->getAllPermissionsFlat($company->id))
        ->not->toContain('store-items.list');
});

test('a full access role stops announcing the permissions of a hidden menu', function () {
    [$user, $company] = createUserWithCompany();
    seedPermissionModule('clients');
    seedPermissionModule('store-items');
    ['leaf' => $leaf] = seedStoreMenu();

    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $leaf->id]);
    session(['current_company_id' => $company->id]);

    expect($user->fresh()->getPermissions())
        ->toContain('clients.list')->not->toContain('store-items.list');
});

test('a custom role keeps the stored permission but stops announcing it while the menu is hidden', function () {
    [$user, $company] = createUserWithCompany();
    ['leaf' => $leaf] = seedStoreMenu();

    $role = assignRoleWithPermissions($user, $company, ['clients.list', 'store-items.list']);
    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $leaf->id]);
    session(['current_company_id' => $company->id]);

    expect($user->fresh()->getPermissions())->toBe(['clients.list'])
        ->and(Role::findOrFail($role->id)->permissions()->pluck('permission')->all())
        ->toContain('store-items.list');

    expect(UserCompany::where('user_id', $user->id)->where('company_id', $company->id)->value('role_id'))->toBe($role->id);
});

test('the role editor receives the tree without the hidden modules', function () {
    [$user, $company] = createUserWithCompany();
    seedPermissionModule('clients');
    seedPermissionModule('store-items');
    ['leaf' => $leaf] = seedStoreMenu();

    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $leaf->id]);

    $response = $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('roles.create', ['company' => $company->id]));

    $response->assertOk();

    $permissions = $response->baseResponse->original->getData()['page']['props']['permissions'];

    expect(collect($permissions['modules'])->pluck('name')->all())->toContain('clients')->not->toContain('store-items')
        ->and($permissions['all'])->not->toContain('store-items.list');
});
