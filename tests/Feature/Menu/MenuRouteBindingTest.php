<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;

use function Pest\Laravel\actingAs;

function createMenu(): Menu
{
    return Menu::create([
        'title' => 'Dashboard',
        'url' => '/dashboard',
        'permission' => 'dashboard.view',
        'icon' => 'home',
        'is_active' => true,
        'order' => 0,
    ]);
}

test('menus.edit binds the menu id and not the company id', function () {
    [$user, $company] = createUserWithCompany();
    $menu = createMenu();

    expect($company->id)->not->toBe($menu->id);

    $response = actingAs($user)->get(route('menus.edit', [
        'company' => $company->id,
        'id' => $menu->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Menus/edit', false)
        ->where('item.id', $menu->id)
    );
});

test('menus.show binds the menu id and not the company id', function () {
    [$user, $company] = createUserWithCompany();
    $menu = createMenu();

    $response = actingAs($user)->get(route('menus.show', [
        'company' => $company->id,
        'id' => $menu->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Menus/show', false)
        ->where('item.id', $menu->id)
    );
});
