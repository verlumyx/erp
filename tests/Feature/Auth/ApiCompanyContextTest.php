<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Menu\Models\Menu;

function seedContextMenus(): void
{
    Menu::create([
        'parent_id' => null,
        'title' => 'Dashboard',
        'url' => '/dashboard',
        'permission' => null,
        'icon' => 'home',
        'is_active' => true,
        'order' => 1,
        'section' => 'main',
    ]);

    Menu::create([
        'parent_id' => null,
        'title' => 'Servicios',
        'url' => '/services',
        'permission' => 'services.list',
        'icon' => 'box',
        'is_active' => true,
        'order' => 2,
        'section' => 'main',
    ]);
}

test('it returns company, permissions and menu in a single request', function () {
    seedContextMenus();
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['services.list', 'menus.list']);
    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/companies/{$company->id}/context");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'company' => ['id', 'name', 'status', 'role_id', 'is_default'],
            'permissions',
            'menu' => ['mainNavItems', 'footerNavItems'],
        ])
        ->assertJsonPath('company.id', $company->id);

    expect($response->json('permissions'))->toContain('services.list');
    expect(collect($response->json('menu.mainNavItems'))->pluck('title'))
        ->toContain('Dashboard', 'Servicios');
});

test('permissions and menu are scoped to the role of that company', function () {
    seedContextMenus();
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['menus.list']);
    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/companies/{$company->id}/context");

    $response->assertSuccessful();

    expect($response->json('permissions'))->toBe(['menus.list']);

    $titles = collect($response->json('menu.mainNavItems'))->pluck('title');
    expect($titles)->toContain('Dashboard');
    expect($titles)->not->toContain('Servicios');
});

test('the context endpoint requires authentication', function () {
    $companyId = \Illuminate\Support\Str::uuid7()->toString();

    $this->getJson("/api/companies/{$companyId}/context")->assertUnauthorized();
});

test('a user cannot fetch the context of a company they do not belong to', function () {
    [$user] = createUserWithCompany();
    $otherCompany = Company::create(['name' => 'Otra', 'status' => 'active', 'created_by' => $user->id]);
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/companies/{$otherCompany->id}/context")
        ->assertForbidden();
});
