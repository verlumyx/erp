<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Menu\Models\Menu;

function seedMenus(): void
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

test('an authenticated user with full access gets the whole menu', function () {
    seedMenus();
    [$user, $company] = createUserWithCompany();
    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/companies/{$company->id}/menu");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'mainNavItems' => [['id', 'title', 'icon', 'url', 'permission', 'children']],
            'footerNavItems',
        ])
        ->assertJsonPath('mainNavItems.0.title', 'Dashboard')
        ->assertJsonPath('mainNavItems.1.title', 'Servicios');

    expect($response->json('mainNavItems'))->toHaveCount(2);
});

test('the menu is filtered by the role permissions', function () {
    seedMenus();
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['menus.list']);
    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/companies/{$company->id}/menu");

    $response->assertSuccessful();

    $titles = collect($response->json('mainNavItems'))->pluck('title');
    expect($titles)->toContain('Dashboard');
    expect($titles)->not->toContain('Servicios');
});

test('the menu endpoint requires authentication', function () {
    $companyId = \Illuminate\Support\Str::uuid7()->toString();

    $this->getJson("/api/companies/{$companyId}/menu")->assertUnauthorized();
});

test('a user cannot fetch the menu of a company they do not belong to', function () {
    seedMenus();
    [$user] = createUserWithCompany();
    $otherCompany = Company::create(['name' => 'Otra', 'status' => 'active', 'created_by' => $user->id]);
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/companies/{$otherCompany->id}/menu")
        ->assertForbidden();
});
