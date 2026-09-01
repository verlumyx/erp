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
                    [
                        'title' => 'Existencias',
                        'icon' => 'Boxes',
                        'url' => '/item-stocks',
                        'permission' => 'item-stocks.list',
                        'order' => 4,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Lotes',
                        'icon' => 'Layers',
                        'url' => '/item-lots',
                        'permission' => 'item-lots.list',
                        'order' => 5,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Series',
                        'icon' => 'Barcode',
                        'url' => '/item-serials',
                        'permission' => 'item-serials.list',
                        'order' => 6,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Kardex',
                        'icon' => 'ArrowLeftRight',
                        'url' => '/inventory-movements',
                        'permission' => 'inventory-movements.list',
                        'order' => 7,
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
                    [
                        'title' => 'Facturas de compra',
                        'icon' => 'ReceiptText',
                        'url' => '/purchase-invoices',
                        'permission' => 'purchase-invoices.list',
                        'order' => 3,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Notas de crédito a proveedor',
                        'icon' => 'FileMinus',
                        'url' => '/purchase-credit-notes',
                        'permission' => 'purchase-credit-notes.list',
                        'order' => 4,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Anticipos a proveedor',
                        'icon' => 'HandCoins',
                        'url' => '/supplier-advances',
                        'permission' => 'supplier-advances.list',
                        'order' => 5,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Pagos a proveedor',
                        'icon' => 'Banknote',
                        'url' => '/supplier-payments',
                        'permission' => 'supplier-payments.list',
                        'order' => 6,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Devoluciones de compras',
                        'icon' => 'Undo2',
                        'url' => '/purchase-returns',
                        'permission' => 'purchase-returns.list',
                        'order' => 7,
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
                    [
                        'title' => 'Facturas de venta',
                        'icon' => 'ReceiptText',
                        'url' => '/sales-invoices',
                        'permission' => 'sales-invoices.list',
                        'order' => 3,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Notas de crédito a cliente',
                        'icon' => 'FileMinus',
                        'url' => '/sales-credit-notes',
                        'permission' => 'sales-credit-notes.list',
                        'order' => 4,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Anticipos de clientes',
                        'icon' => 'HandCoins',
                        'url' => '/client-advances',
                        'permission' => 'client-advances.list',
                        'order' => 5,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Cobros a clientes',
                        'icon' => 'Banknote',
                        'url' => '/client-collections',
                        'permission' => 'client-collections.list',
                        'order' => 6,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Devoluciones de ventas',
                        'icon' => 'Undo2',
                        'url' => '/sales-returns',
                        'permission' => 'sales-returns.list',
                        'order' => 7,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                ],
            ],
            /**
             * Logística: grupo padre sin URL ni permiso propio. Se muestra solo
             * si al menos uno de sus hijos es visible para el usuario.
             */
            [
                'parent_id' => null,
                'title' => 'Logística',
                'icon' => 'Forklift',
                'url' => null,
                'permission' => null,
                'order' => 7,
                'is_active' => true,
                'section' => 'main',
                'children' => [
                    [
                        'title' => 'Despachos',
                        'icon' => 'Truck',
                        'url' => '/dispatches',
                        'permission' => 'dispatches.list',
                        'order' => 1,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Traslados',
                        'icon' => 'ArrowLeftRight',
                        'url' => '/transfers',
                        'permission' => 'transfers.list',
                        'order' => 2,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Entradas',
                        'icon' => 'PackagePlus',
                        'url' => '/entries',
                        'permission' => 'entries.list',
                        'order' => 3,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Rutas',
                        'icon' => 'Route',
                        'url' => '/routes',
                        'permission' => 'routes.list',
                        'order' => 4,
                        'is_active' => true,
                        'section' => 'main',
                    ],
                    [
                        'title' => 'Ajustes',
                        'icon' => 'ClipboardCheck',
                        'url' => '/adjustments',
                        'permission' => 'adjustments.list',
                        'order' => 5,
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
                'title' => 'Configuración de empresa',
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
