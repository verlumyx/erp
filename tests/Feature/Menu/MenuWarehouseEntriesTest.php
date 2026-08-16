<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\GetActiveMenusService;
use Database\Seeders\MenuSeeder;

test('the seeder hangs bodegas and ubicaciones under inventario', function () {
    $this->seed(MenuSeeder::class);

    $group = Menu::query()->where('title', 'Inventario')->firstOrFail();

    $warehouses = Menu::query()
        ->where('title', 'Bodegas')
        ->where('section', 'main')
        ->first();

    $locations = Menu::query()
        ->where('title', 'Ubicaciones')
        ->where('section', 'main')
        ->first();

    expect($warehouses)->not->toBeNull()
        ->and($warehouses->parent_id)->toBe($group->id)
        ->and($warehouses->url)->toBe('/warehouses')
        ->and($warehouses->permission)->toBe('warehouses.list')
        ->and($warehouses->icon)->toBe('Warehouse');

    expect($locations)->not->toBeNull()
        ->and($locations->parent_id)->toBe($group->id)
        ->and($locations->url)->toBe('/warehouse-locations')
        ->and($locations->permission)->toBe('warehouse-locations.list')
        ->and($locations->icon)->toBe('MapPin');
});

test('seeding twice does not duplicate the warehouse entries', function () {
    $this->seed(MenuSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(Menu::query()->where('title', 'Bodegas')->count())->toBe(1);
    expect(Menu::query()->where('title', 'Ubicaciones')->count())->toBe(1);
});

test('a user with only the warehouses permission sees just that child', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['warehouses.list']);
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $found = collect($menus['mainNavItems'])->firstWhere('title', 'Inventario');

    expect($found)->not->toBeNull()
        ->and(collect($found['children'])->pluck('title')->all())
        ->toBe(['Bodegas']);
});

test('the warehouse entries are hidden without their permissions', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    expect(collect($menus['mainNavItems'])->pluck('title')->all())
        ->not->toContain('Inventario');
});
