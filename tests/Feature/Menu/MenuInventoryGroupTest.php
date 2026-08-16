<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\GetActiveMenusService;
use Database\Seeders\MenuSeeder;

test('the menu seeder creates the inventario root group', function () {
    $this->seed(MenuSeeder::class);

    $group = Menu::query()
        ->where('title', 'Inventario')
        ->where('section', 'main')
        ->first();

    expect($group)->not->toBeNull()
        ->and($group->parent_id)->toBeNull()
        ->and($group->url)->toBeNull()
        ->and($group->permission)->toBeNull()
        ->and($group->icon)->toBe('Boxes')
        ->and($group->order)->toBe(4)
        ->and($group->is_active)->toBeTrue();
});

test('seeding twice does not duplicate the inventario group', function () {
    $this->seed(MenuSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(Menu::query()->where('title', 'Inventario')->count())->toBe(1);
});

test('the seeder hangs the item catalog under inventario', function () {
    $this->seed(MenuSeeder::class);

    $group = Menu::query()->where('title', 'Inventario')->firstOrFail();

    $items = Menu::query()
        ->where('title', 'Catálogo de artículos')
        ->where('section', 'main')
        ->first();

    expect($items)->not->toBeNull()
        ->and($items->parent_id)->toBe($group->id)
        ->and($items->url)->toBe('/items')
        ->and($items->permission)->toBe('items.list');
});

test('the inventario group is hidden while none of its children are visible', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    expect(collect($menus['mainNavItems'])->pluck('title')->all())
        ->not->toContain('Inventario');
});

test('the inventario group shows up once a child is visible', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['items.list']);
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $found = collect($menus['mainNavItems'])->firstWhere('title', 'Inventario');

    expect($found)->not->toBeNull()
        ->and($found['url'])->toBeNull()
        ->and(collect($found['children'])->pluck('title')->all())
        ->toBe(['Catálogo de artículos']);
});

test('the inventario group is ordered right after catalogo', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $titles = collect($menus['mainNavItems'])->pluck('title')->all();

    expect(array_search('Inventario', $titles, true))
        ->toBe(array_search('Catálogo', $titles, true) + 1);
});
