<?php

declare(strict_types=1);

use App\Modules\Menu\Models\Menu;
use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * El árbol de permisos del editor de roles se agrupa como el sidebar: el menú
 * es la única fuente de verdad de a qué grupo pertenece cada módulo.
 */
function seedModule(string $name, string $action, int $order): void
{
    $moduleId = (string) Str::uuid();

    DB::table('app_modules')->insert([
        'id' => $moduleId,
        'name' => $name,
        'label' => ucfirst($name),
        'is_active' => true,
        'order' => $order,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Permission::create([
        'module_id' => $moduleId,
        'action' => $action,
        'label' => $action,
        'is_active' => true,
        'order' => 1,
    ]);
}

/** @param array<int, array{title: string, permission: string, order: int}> $children */
function seedMenuGroup(string $title, int $order, array $children): void
{
    $parent = Menu::create([
        'parent_id' => null,
        'title' => $title,
        'icon' => 'ShoppingCart',
        'url' => null,
        'permission' => null,
        'order' => $order,
        'is_active' => true,
        'section' => 'main',
    ]);

    foreach ($children as $child) {
        Menu::create([
            'parent_id' => $parent->id,
            'title' => $child['title'],
            'icon' => 'Circle',
            'url' => '/'.$child['permission'],
            'permission' => $child['permission'],
            'order' => $child['order'],
            'is_active' => true,
            'section' => 'main',
        ]);
    }
}

beforeEach(function () {
    Menu::query()->delete();

    seedModule('purchase-orders', 'purchase-orders.list', 3);
    seedModule('suppliers', 'suppliers.list', 4);
    seedModule('clients', 'clients.list', 2);
    seedModule('users', 'users.list', 1);

    seedMenuGroup('Ventas', 6, [
        ['title' => 'Clientes', 'permission' => 'clients.list', 'order' => 1],
    ]);

    seedMenuGroup('Compras', 5, [
        ['title' => 'Proveedores', 'permission' => 'suppliers.list', 'order' => 1],
        ['title' => 'Órdenes de compra', 'permission' => 'purchase-orders.list', 'order' => 2],
    ]);

    // El pie de página no cuelga de ningún grupo: sigue suelto.
    Menu::create([
        'parent_id' => null,
        'title' => 'Usuarios',
        'icon' => 'UserCheck',
        'url' => '/users',
        'permission' => 'users.list',
        'order' => 1,
        'is_active' => true,
        'section' => 'footer',
    ]);
});

test('each module carries the sidebar group it hangs from', function () {
    $modules = collect(app(PermissionRepositoryInterface::class)->getAllGroupedByModule())
        ->keyBy('name');

    expect($modules['suppliers']['group']['title'])->toBe('Compras')
        ->and($modules['purchase-orders']['group']['title'])->toBe('Compras')
        ->and($modules['clients']['group']['title'])->toBe('Ventas');
});

test('a module outside every menu group carries no group', function () {
    $modules = collect(app(PermissionRepositoryInterface::class)->getAllGroupedByModule())
        ->keyBy('name');

    expect($modules['users']['group'])->toBeNull();
});

test('modules come ordered by menu group and, inside it, by menu order', function () {
    $names = collect(app(PermissionRepositoryInterface::class)->getAllGroupedByModule())
        ->pluck('name')
        ->all();

    expect($names)->toBe([
        'suppliers',        // Compras (grupo 5), primer hijo
        'purchase-orders',  // Compras (grupo 5), segundo hijo
        'clients',          // Ventas (grupo 6)
        'users',            // sin grupo: al final
    ]);
});
