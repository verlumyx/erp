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
            /**
             * 'system_owner' es un permiso reservado: este menú solo lo ve el dueño del sistema.
             */
            [
                'id' => (string) Str::uuid7(),
                'parent_id' => null,
                'title' => 'Empresas',
                'icon' => 'Building2',
                'url' => '/companies',
                'permission' => 'system_owner',
                'order' => 3,
                'is_active' => true,
                'section' => 'footer',
            ],
        ];

        foreach ($menus as $menu) {
            Menu::query()->updateOrCreate(
                ['title' => $menu['title'], 'section' => $menu['section']],
                $menu
            );
        }
    }
}
