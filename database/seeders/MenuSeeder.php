<?php

namespace Database\Seeders;

use App\Modules\Menu\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            // Main navigation
            [
                'parent_id' => null,
                'title' => 'Dashboard',
                'icon' => 'LayoutGrid',
                'url' => '/dashboard',
                'permission' => null,
                'order' => 1,
                'is_active' => true,
                'section' => 'main',
            ],
            /**
             * Catálogo: grupo padre sin URL ni permiso propio. Se muestra solo
             * si al menos uno de sus hijos es visible para el usuario.
             */
            [
                'parent_id' => null,
                'title' => 'Catálogo',
                'icon' => 'Library',
                'url' => null,
                'permission' => null,
                'order' => 3,
                'is_active' => true,
                'section' => 'main',
                'children' => [
                    [
                        'title' => 'Categorías',
                        'icon' => 'Tags',
                        'url' => '/categories',
                        'permission' => 'categories.list',
                        'order' => 1,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Unidades de medida',
                        'icon' => 'Ruler',
                        'url' => '/measurement-units',
                        'permission' => 'measurement-units.list',
                        'order' => 2,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Tipos de proveedor',
                        'icon' => 'Truck',
                        'url' => '/supplier-types',
                        'permission' => 'supplier-types.list',
                        'order' => 3,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Tipos de cliente',
                        'icon' => 'ContactRound',
                        'url' => '/client-types',
                        'permission' => 'client-types.list',
                        'order' => 4,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Listas de precio',
                        'icon' => 'DollarSign',
                        'url' => '/price-lists',
                        'permission' => 'price-lists.list',
                        'order' => 5,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Tasas',
                        'icon' => 'Coins',
                        'url' => '/exchange-rates',
                        'permission' => 'exchange-rates.list',
                        'order' => 6,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Impuestos',
                        'icon' => 'Percent',
                        'url' => '/taxes',
                        'permission' => 'taxes.list',
                        'order' => 7,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                ],
            ],
            /**
             * Inventario: grupo padre sin URL ni permiso propio. Permanece oculto
             * hasta que tenga al menos un hijo visible (Artículos, Bodegas, etc.).
             */
            [
                'parent_id' => null,
                'title' => 'Inventario',
                'icon' => 'Boxes',
                'url' => null,
                'permission' => null,
                'order' => 4,
                'is_active' => true,
                'section' => 'main',
                'children' => [
                    [
                        'title' => 'Catálogo de artículos',
                        'icon' => 'Package',
                        'url' => '/items',
                        'permission' => 'items.list',
                        'order' => 1,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Bodegas',
                        'icon' => 'Warehouse',
                        'url' => '/warehouses',
                        'permission' => 'warehouses.list',
                        'order' => 2,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Ubicaciones',
                        'icon' => 'MapPin',
                        'url' => '/warehouse-locations',
                        'permission' => 'warehouse-locations.list',
                        'order' => 3,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                ],
            ],
            /**
             * Compras: grupo padre sin URL ni permiso propio. Permanece oculto
             * hasta que tenga al menos un hijo visible (Proveedores, Órdenes de
             * compra, Facturas de compra, etc.).
             */
            [
                'parent_id' => null,
                'title' => 'Compras',
                'icon' => 'ShoppingCart',
                'url' => null,
                'permission' => null,
                'order' => 5,
                'is_active' => true,
                'section' => 'main',
                'children' => [
                    [
                        'title' => 'Proveedores',
                        'icon' => 'Truck',
                        'url' => '/suppliers',
                        'permission' => 'suppliers.list',
                        'order' => 1,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Órdenes de compra',
                        'icon' => 'ClipboardList',
                        'url' => '/purchase-orders',
                        'permission' => 'purchase-orders.list',
                        'order' => 2,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                ],
            ],
            /**
             * Ventas: grupo padre sin URL ni permiso propio. Se muestra solo
             * si al menos uno de sus hijos es visible para el usuario.
             */
            [
                'parent_id' => null,
                'title' => 'Ventas',
                'icon' => 'ShoppingBag',
                'url' => null,
                'permission' => null,
                'order' => 6,
                'is_active' => true,
                'section' => 'main',
                'children' => [
                    [
                        'title' => 'Clientes',
                        'icon' => 'Contact',
                        'url' => '/clients',
                        'permission' => 'clients.list',
                        'order' => 1,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Órdenes de venta',
                        'icon' => 'ClipboardList',
                        'url' => '/sales-orders',
                        'permission' => 'sales-orders.list',
                        'order' => 2,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                ],
            ],

            // Footer navigation (admin section)
            [
                'parent_id' => null,
                'title' => 'Usuarios',
                'icon' => 'UserCheck',
                'url' => '/users',
                'permission' => 'users.list',
                'order' => 1,
                'is_active' => true,
                'section' => 'footer',
            ],
            [
                'parent_id' => null,
                'title' => 'Roles',
                'icon' => 'Users',
                'url' => '/roles',
                'permission' => 'roles.list',
                'order' => 2,
                'is_active' => true,
                'section' => 'footer',
            ],
            [
                'parent_id' => null,
                'title' => 'Configuración',
                'icon' => 'Settings',
                'url' => '/configuration',
                'permission' => 'configuration.show',
                'order' => 3,
                'is_active' => true,
                'section' => 'footer',
            ],
            /**
             * 'system_owner' es un permiso reservado: este menú solo lo ve el dueño del sistema.
             */
            [
                'parent_id' => null,
                'title' => 'Empresas',
                'icon' => 'Building2',
                'url' => '/companies',
                'permission' => 'system_owner',
                'order' => 4,
                'is_active' => true,
                'section' => 'footer',
            ],
        ];

        foreach ($menus as $menu) {
            $children = $menu['children'] ?? [];
            unset($menu['children']);

            $parent = $this->upsert($menu);

            foreach ($children as $child) {
                $this->upsert([...$child, 'parent_id' => $parent->id]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $menu
     */
    private function upsert(array $menu): Menu
    {
        return Menu::query()->updateOrCreate(
            ['title' => $menu['title'], 'section' => $menu['section']],
            $menu
        );
    }
}
