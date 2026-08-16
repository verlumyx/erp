<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\GetActiveMenusService;
use Database\Seeders\MenuSeeder;

test('the menu seeder creates the root group', function (string $title, string $icon, int $order) {
    $this->seed(MenuSeeder::class);

    $group = Menu::query()
        ->where('title', $title)
        ->where('section', 'main')
        ->first();

    expect($group)->not->toBeNull()
        ->and($group->parent_id)->toBeNull()
        ->and($group->url)->toBeNull()
        ->and($group->permission)->toBeNull()
        ->and($group->icon)->toBe($icon)
        ->and($group->order)->toBe($order)
        ->and($group->is_active)->toBeTrue();
})->with([
    'compras' => ['Compras', 'ShoppingCart', 5],
    'ventas' => ['Ventas', 'ShoppingBag', 6],
]);

test('seeding twice does not duplicate the group', function (string $title) {
    $this->seed(MenuSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(Menu::query()->where('title', $title)->count())->toBe(1);
})->with(['Compras', 'Ventas']);

test('the group is hidden while it has no children', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    Menu::create([
        'parent_id' => null,
        'title' => 'Reportes',
        'url' => null,
        'permission' => null,
        'icon' => 'ChartBar',
        'section' => 'main',
        'is_active' => true,
        'order' => 7,
    ]);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    expect(collect($menus['mainNavItems'])->pluck('title')->all())->not->toContain('Reportes');
});

test('compras ships with proveedores as its first child', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $compras = collect($menus['mainNavItems'])->firstWhere('title', 'Compras');

    expect($compras)->not->toBeNull()
        ->and($compras['url'])->toBeNull()
        ->and(collect($compras['children'])->pluck('title')->all())->toBe(['Proveedores'])
        ->and(collect($compras['children'])->firstWhere('title', 'Proveedores')['url'])
        ->toBe('/suppliers');
});

test('proveedores is hidden for a user without the suppliers.list permission', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    expect(collect($menus['mainNavItems'])->pluck('title')->all())->not->toContain('Compras');
});

test('ventas ships with clientes as its first child', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $ventas = collect($menus['mainNavItems'])->firstWhere('title', 'Ventas');

    expect($ventas)->not->toBeNull()
        ->and($ventas['url'])->toBeNull()
        ->and(collect($ventas['children'])->pluck('title')->all())->toBe(['Clientes'])
        ->and(collect($ventas['children'])->firstWhere('title', 'Clientes')['url'])
        ->toBe('/clients');
});

test('clientes is hidden for a user without the clients.list permission', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    expect(collect($menus['mainNavItems'])->pluck('title')->all())->not->toContain('Ventas');
});

/**
 * Clientes dejó de ser una entrada de primer nivel: ahora cuelga de Ventas,
 * como establece docs/ventas.md.
 */
test('clientes is no longer a root menu entry', function () {
    $this->seed(MenuSeeder::class);

    $clientes = Menu::query()->where('title', 'Clientes')->where('section', 'main')->firstOrFail();
    $ventas = Menu::query()->where('title', 'Ventas')->where('section', 'main')->firstOrFail();

    expect($clientes->parent_id)->toBe($ventas->id)
        ->and($clientes->url)->toBe('/clients');
});

test('compras and ventas are ordered after inventario', function () {
    $this->seed(MenuSeeder::class);

    $orders = Menu::query()
        ->whereNull('parent_id')
        ->where('section', 'main')
        ->orderBy('order')
        ->pluck('title')
        ->all();

    expect($orders)->toBe(['Dashboard', 'Catálogo', 'Inventario', 'Compras', 'Ventas']);
});
