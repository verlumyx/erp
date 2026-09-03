<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\GetActiveMenusService;
use Database\Seeders\MenuSeeder;

test('the menu seeder creates the logistica root group', function () {
    $this->seed(MenuSeeder::class);

    $group = Menu::query()
        ->where('title', 'Logística')
        ->where('section', 'main')
        ->first();

    expect($group)->not->toBeNull()
        ->and($group->parent_id)->toBeNull()
        ->and($group->url)->toBeNull()
        ->and($group->permission)->toBeNull()
        ->and($group->icon)->toBe('Forklift')
        ->and($group->order)->toBe(7)
        ->and($group->is_active)->toBeTrue();
});

test('seeding twice does not duplicate the logistica group', function () {
    $this->seed(MenuSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(Menu::query()->where('title', 'Logística')->count())->toBe(1);
});

/**
 * El grupo se asoma en cuanto tiene un hijo visible. Despachos es el primero;
 * faltan Traslados, Entradas, Rutas y Ajustes.
 */
test('the logistica group shows up once it has a visible child', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    $group = collect($menus['mainNavItems'])->firstWhere('title', 'Logística');

    expect($group)->not->toBeNull();
    expect(collect($group['children'])->pluck('title')->all())->toContain('Despachos');
});

test('the logistica group is hidden for a user who cannot see any of its modules', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    /** Sin permisos de ningún módulo de Logística el grupo se queda sin hijos. */
    assignRoleWithPermissions($user, $company, ['clients.list']);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    expect(collect($menus['mainNavItems'])->pluck('title')->all())
        ->not->toContain('Logística');
});

test('logistica is ordered after ventas', function () {
    $this->seed(MenuSeeder::class);

    $orders = Menu::query()
        ->whereNull('parent_id')
        ->where('section', 'main')
        ->orderBy('order')
        ->pluck('title')
        ->all();

    expect($orders)->toBe(['Dashboard', 'Catálogo', 'Inventario', 'Compras', 'Ventas', 'Logística', 'Tienda']);
});
