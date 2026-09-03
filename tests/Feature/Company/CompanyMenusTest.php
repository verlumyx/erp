<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Shared\Models\CompanyDisabledMenu;
use Database\Seeders\MenuSeeder;

use function Pest\Laravel\actingAs;

/**
 * El dueño del sistema decide qué menús ve cada empresa. Lo que guarda es la
 * lista de menús DESHABILITADOS: sin filas la empresa ve todo lo que su rol
 * permite.
 */
function storeGroupLeafIds(): array
{
    $store = Menu::query()->where('title', 'Tienda')->whereNull('parent_id')->firstOrFail();

    return Menu::query()->where('parent_id', $store->id)->pluck('id')->all();
}

test('a non owner cannot open the company menus page', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('companies.menus.edit', ['company' => $company->id, 'id' => $company->id]))
        ->assertForbidden();
});

test('a non owner cannot update the company menus', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('companies.menus.update', ['company' => $company->id, 'id' => $company->id]), [
            'disabled_menus' => storeGroupLeafIds(),
        ])
        ->assertForbidden();

    expect(CompanyDisabledMenu::query()->count())->toBe(0);
});

test('the owner sees the full tree without the system owner menu and the current disabled ids', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);
    $this->seed(MenuSeeder::class);

    [$publicationsId] = storeGroupLeafIds();
    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $publicationsId]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('companies.menus.edit', ['company' => $company->id, 'id' => $company->id]));

    $response->assertOk();

    $props = $response->baseResponse->original->getData()['page']['props'];

    $mainTitles = collect($props['menuTree']['mainNavItems'])->pluck('title')->all();
    $footerTitles = collect($props['menuTree']['footerNavItems'])->pluck('title')->all();
    $store = collect($props['menuTree']['mainNavItems'])->firstWhere('title', 'Tienda');

    expect($mainTitles)->toContain('Tienda')
        ->and(collect($store['children'])->pluck('title')->all())->toContain('Publicaciones')
        ->and($footerTitles)->toContain('Usuarios')
        ->and($footerTitles)->not->toContain('Empresas')
        ->and($props['disabled_menus'])->toBe([$publicationsId]);
});

test('the owner can disable menus and saving again replaces the whole set', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);
    $this->seed(MenuSeeder::class);

    $storeIds = storeGroupLeafIds();
    $session = ['current_company_id' => $company->id];
    $url = route('companies.menus.update', ['company' => $company->id, 'id' => $company->id]);

    actingAs($user)->withSession($session)
        ->put($url, ['disabled_menus' => $storeIds])
        ->assertRedirect(route('companies.show', ['company' => $company->id, 'id' => $company->id]));

    expect(CompanyDisabledMenu::query()->where('company_id', $company->id)->pluck('menu_id')->sort()->values()->all())
        ->toBe(collect($storeIds)->sort()->values()->all());

    actingAs($user)->withSession($session)
        ->put($url, ['disabled_menus' => [$storeIds[0]]])
        ->assertRedirect();

    expect(CompanyDisabledMenu::query()->where('company_id', $company->id)->pluck('menu_id')->all())
        ->toBe([$storeIds[0]]);

    actingAs($user)->withSession($session)
        ->put($url, ['disabled_menus' => []])
        ->assertRedirect();

    expect(CompanyDisabledMenu::query()->where('company_id', $company->id)->count())->toBe(0);
});

test('the system owner menu is never stored as disabled', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);
    $this->seed(MenuSeeder::class);

    $companiesMenu = Menu::query()->where('permission', 'system_owner')->firstOrFail();
    [$publicationsId] = storeGroupLeafIds();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('companies.menus.update', ['company' => $company->id, 'id' => $company->id]), [
            'disabled_menus' => [$companiesMenu->id, $publicationsId],
        ])
        ->assertRedirect();

    expect(CompanyDisabledMenu::query()->where('company_id', $company->id)->pluck('menu_id')->all())
        ->toBe([$publicationsId]);
});

test('an unknown menu id is rejected', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->from(route('companies.menus.edit', ['company' => $company->id, 'id' => $company->id]))
        ->put(route('companies.menus.update', ['company' => $company->id, 'id' => $company->id]), [
            'disabled_menus' => ['0199a000-0000-7000-8000-000000000000'],
        ])
        ->assertSessionHasErrors('disabled_menus.0');
});

/**
 * La prop del árbol no puede llamarse `menus`: ese nombre lo ocupa la prop que
 * `HandleInertiaRequests` comparte con todas las páginas para dibujar el
 * sidebar, y una prop de página la tapa.
 *
 * Las dos tienen la misma forma, así que el sidebar usaba este árbol sin
 * enterarse y sus enlaces salían sin el prefijo de la empresa: desde esta
 * pantalla, entrar a cualquier módulo daba 404.
 */
test('the page does not shadow the shared sidebar menus', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);
    $this->seed(MenuSeeder::class);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('companies.menus.edit', ['company' => $company->id, 'id' => $company->id]));

    $response->assertOk();

    $props = $response->baseResponse->original->getData()['page']['props'];

    /** El árbol que se edita viaja con su propio nombre. */
    expect($props)->toHaveKeys(['menuTree', 'menus']);

    /** Y el del sidebar sigue llegando con las URLs de la empresa. */
    $urls = collect($props['menus']['mainNavItems'])
        ->flatMap(fn (array $item): array => [
            $item['url'],
            ...collect($item['children'] ?? [])->pluck('url')->all(),
        ])
        ->filter()
        ->all();

    expect($urls)->not->toBeEmpty();

    foreach ($urls as $url) {
        expect($url)->toStartWith('/'.$company->id.'/');
    }
});
