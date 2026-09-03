<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\GetActiveMenusService;
use App\Modules\Shared\Models\CompanyDisabledMenu;
use Database\Seeders\MenuSeeder;
use Illuminate\Support\Str;

/**
 * Un menú deshabilitado para una empresa desaparece de su árbol, aunque el
 * rol del usuario tenga el permiso. Las demás empresas no se enteran.
 */
function storeChildren(): array
{
    $store = Menu::query()->where('title', 'Tienda')->whereNull('parent_id')->firstOrFail();

    return Menu::query()->where('parent_id', $store->id)->orderBy('order')->get()->all();
}

function storeTitlesFor(GetActiveMenusService $service, $user, string $companyId): array
{
    session(['current_company_id' => $companyId]);

    $menus = $service->execute($user->fresh(), $companyId);
    $store = collect($menus['mainNavItems'])->firstWhere('title', 'Tienda');

    return $store === null ? [] : collect($store['children'])->pluck('title')->all();
}

test('a disabled leaf disappears for that company only', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    $otherCompany = Company::factory()->create();
    $user->companies()->attach($otherCompany->id, [
        'id' => (string) Str::uuid(),
        'role_id' => $user->companies()->first()->pivot->role_id,
        'status' => 'active',
    ]);

    [$publications] = storeChildren();
    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $publications->id]);

    $service = app(GetActiveMenusService::class);

    expect(storeTitlesFor($service, $user, $company->id))->not->toContain('Publicaciones')
        ->toContain('Compradores')
        ->and(storeTitlesFor($service, $user, $otherCompany->id))->toContain('Publicaciones');
});

test('disabling every leaf of a group hides the group itself', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    foreach (storeChildren() as $child) {
        CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $child->id]);
    }

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh(), $company->id);
    $titles = collect($menus['mainNavItems'])->pluck('title')->all();

    expect($titles)->not->toContain('Tienda')
        ->and($titles)->toContain('Ventas');
});

test('the system owner menu stays visible to the owner even with a disabled row', function () {
    [$user, $company] = createUserWithCompany();
    $user->update(['is_system_owner' => true]);
    $this->seed(MenuSeeder::class);

    $companiesMenu = Menu::query()->where('permission', 'system_owner')->firstOrFail();
    CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $companiesMenu->id]);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh(), $company->id);

    expect(collect($menus['footerNavItems'])->pluck('title')->all())->toContain('Empresas');
});

test('without a company id nothing is hidden', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    foreach (storeChildren() as $child) {
        CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $child->id]);
    }

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());

    expect(collect($menus['mainNavItems'])->pluck('title')->all())->toContain('Tienda');
});

test('the sidebar shared with the page hides the disabled menus of the current company', function () {
    [$user, $company] = createUserWithCompany();
    $this->seed(MenuSeeder::class);

    foreach (storeChildren() as $child) {
        CompanyDisabledMenu::create(['company_id' => $company->id, 'menu_id' => $child->id]);
    }

    $response = $this->actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get("/{$company->id}/dashboard");

    $response->assertOk();

    $menus = $response->baseResponse->original->getData()['page']['props']['menus'];

    expect(collect($menus['mainNavItems'])->pluck('title')->all())->not->toContain('Tienda');
});
