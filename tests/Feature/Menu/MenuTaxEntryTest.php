<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\GetActiveMenusService;
use Database\Seeders\MenuSeeder;

test('the seeder hangs impuestos under catalogo', function () {
    $this->seed(MenuSeeder::class);

    $group = Menu::query()->where('title', 'Catálogo')->firstOrFail();

    $taxes = Menu::query()
        ->where('title', 'Impuestos')
        ->where('section', 'main')
        ->first();

    expect($taxes)->not->toBeNull()
        ->and($taxes->parent_id)->toBe($group->id)
        ->and($taxes->url)->toBe('/taxes')
        ->and($taxes->permission)->toBe('taxes.list')
        ->and($taxes->icon)->toBe('Percent')
        ->and($taxes->is_active)->toBeTrue();
});

test('seeding twice does not duplicate the impuestos entry', function () {
    $this->seed(MenuSeeder::class);
    $this->seed(MenuSeeder::class);

    expect(Menu::query()->where('title', 'Impuestos')->count())->toBe(1);
});

test('a user with only the taxes permission sees just that catalog child', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['taxes.list']);
    $this->seed(MenuSeeder::class);

    session(['current_company_id' => $company->id]);

    $menus = app(GetActiveMenusService::class)->execute($user->fresh());
    $found = collect($menus['mainNavItems'])->firstWhere('title', 'Catálogo');

    expect($found)->not->toBeNull()
        ->and(collect($found['children'])->pluck('title')->all())
        ->toBe(['Impuestos']);
});

test('the impuestos url is prefixed with the company when rendered', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['taxes.list']);
    $this->seed(MenuSeeder::class);

    $response = $this->actingAs($user->fresh())
        ->withSession(['current_company_id' => $company->id])
        ->get(route('taxes.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($company) {
        $group = collect($page->toArray()['props']['menus']['mainNavItems'])
            ->firstWhere('title', 'Catálogo');

        expect($group['children'][0]['url'])->toBe('/'.$company->id.'/taxes');
    });
});
