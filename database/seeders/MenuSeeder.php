<?php

namespace Database\Seeders;

use App\Modules\Menu\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            // Main navigation
            [
                'id' => (string) Str::uuid7(),
                'parent_id' => null,
                'title' => 'Dashboard',
                'icon' => 'LayoutGrid',
                'url' => '/dashboard',
                'permission' => null,
                'order' => 1,
                'is_active' => true,
                'section' => 'main',
            ],

            // Footer navigation (admin section)
            [
                'id' => (string) Str::uuid7(),
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
                'id' => (string) Str::uuid7(),
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
                'id' => (string) Str::uuid7(),
                'parent_id' => null,
                'title' => 'Clientes',
                'icon' => 'Contact',
                'url' => '/clients',
                'permission' => 'clients.list',
                'order' => 2,
                'is_active' => true,
                'section' => 'main',
            ],
        ];

        foreach ($menus as $menu) {
            Menu::query()->updateOrCreate(
                ['title' => $menu['title'], 'section' => $menu['section']],
                $menu
            );
        }

        $this->seedCatalogo();
        $this->seedInventario();
        $this->seedVentas();
        $this->seedTransaccionesManuales();
        $this->seedReembolsos();
        $this->seedReportes();
    }

    /**
     * Catálogo (grupo) › Servicios y Planes — menús anidados en la navegación principal.
     *
     * Se usa firstOrCreate (sin 'id') para no reescribir el PK de un menú existente:
     * Catálogo es referenciado por sus hijos vía FK y actualizar su id rompería la relación.
     */
    private function seedCatalogo(): void
    {
        $catalogo = Menu::query()->firstOrCreate(
            ['title' => 'Catálogo', 'section' => 'main'],
            [
                'parent_id' => null,
                'icon' => 'LibraryBig',
                'url' => '/services',
                'permission' => 'services.list',
                'order' => 3,
                'is_active' => true,
            ]
        );

        Menu::query()->firstOrCreate(
            ['title' => 'Servicios', 'section' => 'main'],
            [
                'parent_id' => $catalogo->id,
                'icon' => 'Clapperboard',
                'url' => '/services',
                'permission' => 'services.list',
                'order' => 1,
                'is_active' => true,
            ]
        );

        Menu::query()->firstOrCreate(
            ['title' => 'Planes', 'section' => 'main'],
            [
                'parent_id' => $catalogo->id,
                'icon' => 'Package',
                'url' => '/plans',
                'permission' => 'plans.list',
                'order' => 2,
                'is_active' => true,
            ]
        );
    }

    /**
     * Inventario (grupo) › Cuentas — menú anidado en la navegación principal.
     *
     * Se usa firstOrCreate (sin 'id') para no reescribir el PK de un menú existente:
     * Inventario es referenciado por sus hijos vía FK y actualizar su id rompería la relación.
     */
    private function seedInventario(): void
    {
        $inventario = Menu::query()->firstOrCreate(
            ['title' => 'Cuentas', 'section' => 'main'],
            [
                'parent_id' => null,
                'icon' => 'Boxes',
                'url' => '/accounts',
                'permission' => 'accounts.list',
                'order' => 4,
                'is_active' => true,
            ]
        );
    }

    /**
     * Ventas — menú de la navegación principal.
     *
     * Se usa firstOrCreate (sin 'id') para no reescribir el PK de un menú existente.
     */
    private function seedVentas(): void
    {
        Menu::query()->firstOrCreate(
            ['title' => 'Ventas', 'section' => 'main'],
            [
                'parent_id' => null,
                'icon' => 'ShoppingCart',
                'url' => '/sales',
                'permission' => 'sales.list',
                'order' => 5,
                'is_active' => true,
            ]
        );
    }

    /**
     * Transacciones manuales — menú de la navegación principal.
     *
     * Se usa firstOrCreate (sin 'id') para no reescribir el PK de un menú existente.
     */
    private function seedTransaccionesManuales(): void
    {
        Menu::query()->firstOrCreate(
            ['title' => 'Transacciones manuales', 'section' => 'main'],
            [
                'parent_id' => null,
                'icon' => 'NotebookPen',
                'url' => '/manual-transactions',
                'permission' => 'manual-transactions.list',
                'order' => 7,
                'is_active' => true,
            ]
        );
    }

    /**
     * Reembolsos — menú de la navegación principal.
     *
     * Se usa firstOrCreate (sin 'id') para no reescribir el PK de un menú existente.
     */
    private function seedReembolsos(): void
    {
        Menu::query()->firstOrCreate(
            ['title' => 'Reembolsos', 'section' => 'main'],
            [
                'parent_id' => null,
                'icon' => 'Undo2',
                'url' => '/refunds',
                'permission' => 'refunds.list',
                'order' => 6,
                'is_active' => true,
            ]
        );
    }

    /**
     * Reportes (grupo) › Movimientos — menú anidado en la navegación principal.
     *
     * Se usa firstOrCreate (sin 'id') para no reescribir el PK de un menú existente:
     * Reportes es referenciado por sus hijos vía FK y actualizar su id rompería la relación.
     */
    private function seedReportes(): void
    {
        $reportes = Menu::query()->firstOrCreate(
            ['title' => 'Reportes', 'section' => 'main'],
            [
                'parent_id' => null,
                'icon' => 'ChartColumn',
                'url' => '/reports/movements',
                'permission' => 'reports.movements',
                'order' => 8,
                'is_active' => true,
            ]
        );

        Menu::query()->firstOrCreate(
            ['title' => 'Movimientos', 'section' => 'main'],
            [
                'parent_id' => $reportes->id,
                'icon' => 'ArrowLeftRight',
                'url' => '/reports/movements',
                'permission' => 'reports.movements',
                'order' => 1,
                'is_active' => true,
            ]
        );

        Menu::query()->firstOrCreate(
            ['title' => 'Ingresos y Gastos', 'section' => 'main'],
            [
                'parent_id' => $reportes->id,
                'icon' => 'Scale',
                'url' => '/reports/income-expenses',
                'permission' => 'reports.income_expenses',
                'order' => 2,
                'is_active' => true,
            ]
        );

        Menu::query()->firstOrCreate(
            ['title' => 'Servicio / Plan', 'section' => 'main'],
            [
                'parent_id' => $reportes->id,
                'icon' => 'PieChart',
                'url' => '/reports/service-plan',
                'permission' => 'reports.service_plan',
                'order' => 3,
                'is_active' => true,
            ]
        );

        Menu::query()->firstOrCreate(
            ['title' => 'Vencimientos', 'section' => 'main'],
            [
                'parent_id' => $reportes->id,
                'icon' => 'CalendarClock',
                'url' => '/reports/expirations',
                'permission' => 'reports.expirations',
                'order' => 4,
                'is_active' => true,
            ]
        );
    }
}
