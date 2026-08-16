<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\GetActiveMenusService;

/**
 * Create the "Catálogo" group with its catalog modules nested inside it.
 */
function createCatalogGroup(): Menu
{
    $group = Menu::create([
        'title' => 'Catálogo',
        'url' => null,
        'permission' => null,
        'icon' => 'Library',
        'section' => 'main',
        'is_active' => true,
        'order' => 3,
    ]);

    Menu::create([
        'parent_id' => $group->id,
        'title' => 'Categorías',
        'url' => '/categories',
        'permission' => 'categories.list',
        'icon' => 'Tags',
        'section' => 'main',
        'is_active' => true,
        'order' => 1,
    ]);

    Menu::create([
        'parent_id' => $group->id,
        'title' => 'Unidades de medida',
        'url' => '/measurement-units',
        'permission' => 'measurement-units.list',
        'icon' => 'Ruler',
        'section' => 'main',
        'is_active' => true,
        'order' => 2,
    ]);

    return $group;
}

test('categorias is nested under the catalogo group', function () {
    [$user, $company] = createUserWithCompany();
    createCatalogGroup();

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    $group = collect($menus['mainNavItems'])->firstWhere('title', 'Catálogo');

    expect($group)->not->toBeNull();
    expect($group['url'])->toBeNull();
    expect($group['children'])->toHaveCount(2);
    expect(collect($group['children'])->pluck('title')->all())
        ->toBe(['Categorías', 'Unidades de medida']);
    expect(collect($group['children'])->pluck('url')->all())
        ->toBe(['/categories', '/measurement-units']);
});

test('the catalog modules are no longer root menu items', function () {
    [$user, $company] = createUserWithCompany();
    createCatalogGroup();

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $rootTitles = collect($menus['mainNavItems'])->pluck('title')->all();

    expect($rootTitles)->toContain('Catálogo')
        ->and($rootTitles)->not->toContain('Categorías')
        ->and($rootTitles)->not->toContain('Unidades de medida');
});

test('only the catalog children the user has permission for are listed', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['measurement-units.list']);
    createCatalogGroup();

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $group = collect($menus['mainNavItems'])->firstWhere('title', 'Catálogo');

    expect($group)->not->toBeNull();
    expect(collect($group['children'])->pluck('title')->all())
        ->toBe(['Unidades de medida']);
});

test('a child menu is hidden when the user lacks its permission', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);
    createCatalogGroup();

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $titles = collect($menus['mainNavItems'])->pluck('title')->all();

    // El grupo no tiene URL propia y se queda sin hijos visibles: se oculta entero.
    expect($titles)->not->toContain('Catálogo');
});

test('the group stays visible when at least one child is visible', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['categories.list']);

    $group = createCatalogGroup();

    Menu::create([
        'parent_id' => $group->id,
        'title' => 'Impuestos',
        'url' => '/taxes',
        'permission' => 'taxes.list',
        'icon' => 'Percent',
        'section' => 'main',
        'is_active' => true,
        'order' => 3,
    ]);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $found = collect($menus['mainNavItems'])->firstWhere('title', 'Catálogo');

    expect($found)->not->toBeNull();
    expect(collect($found['children'])->pluck('title')->all())
        ->toBe(['Categorías']);
});

test('an inactive child is excluded from the group', function () {
    [$user, $company] = createUserWithCompany();

    $group = createCatalogGroup();

    Menu::create([
        'parent_id' => $group->id,
        'title' => 'Tasas',
        'url' => '/exchange-rates',
        'permission' => null,
        'icon' => 'Coins',
        'section' => 'main',
        'is_active' => false,
        'order' => 3,
    ]);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $found = collect($menus['mainNavItems'])->firstWhere('title', 'Catálogo');

    expect(collect($found['children'])->pluck('title')->all())
        ->toBe(['Categorías', 'Unidades de medida']);
});

test('the catalogo group url is prefixed with the company on its children only', function () {
    [$user, $company] = createUserWithCompany();
    createCatalogGroup();

    $response = $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('categories.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($company) {
        $group = collect($page->toArray()['props']['menus']['mainNavItems'])
            ->firstWhere('title', 'Catálogo');

        expect($group['url'])->toBeNull();
        expect($group['children'][0]['url'])->toBe('/'.$company->id.'/categories');
    });
});
