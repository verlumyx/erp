<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = (string) Str::uuid();

        // 1. Module
        DB::table('app_modules')->insertOrIgnore([
            'id' => $moduleId,
            'name' => 'reports',
            'label' => 'Reportes',
            'description' => 'Reportes financieros, de cartera y de gestión',
            'icon' => 'BarChart3',
            'is_active' => true,
            'order' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existingModule = DB::table('app_modules')->where('name', 'reports')->first();
        $targetModuleId = $existingModule ? $existingModule->id : $moduleId;

        // 2. Permissions
        $permissions = [
            ['action' => 'reports.accounts-receivable', 'label' => 'Ver reporte de cuentas por cobrar', 'order' => 1],
            ['action' => 'reports.accounts-payable', 'label' => 'Ver reporte de cuentas por pagar', 'order' => 2],
            ['action' => 'reports.income-expense', 'label' => 'Ver reporte de ingresos y gastos', 'order' => 3],
            ['action' => 'reports.export', 'label' => 'Exportar reportes', 'order' => 4],
        ];

        foreach ($permissions as $perm) {
            DB::table('app_permissions')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'module_id' => $targetModuleId,
                'action' => $perm['action'],
                'label' => $perm['label'],
                'is_active' => true,
                'order' => $perm['order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Menus
        $parentMenu = DB::table('app_menus')->where('title', 'Reportes')->where('section', 'main')->first();
        if (! $parentMenu) {
            $parentMenuId = (string) Str::uuid();
            DB::table('app_menus')->insert([
                'id' => $parentMenuId,
                'parent_id' => null,
                'title' => 'Reportes',
                'icon' => 'BarChart3',
                'url' => null,
                'permission' => null,
                'order' => 9,
                'is_active' => true,
                'section' => 'main',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $parentMenuId = $parentMenu->id;
        }

        $children = [
            [
                'title' => 'Cuentas por cobrar',
                'icon' => 'ArrowDownLeft',
                'url' => '/reports/accounts-receivable',
                'permission' => 'reports.accounts-receivable',
                'order' => 1,
            ],
            [
                'title' => 'Cuentas por pagar',
                'icon' => 'ArrowUpRight',
                'url' => '/reports/accounts-payable',
                'permission' => 'reports.accounts-payable',
                'order' => 2,
            ],
            [
                'title' => 'Ingresos y gastos',
                'icon' => 'TrendingUp',
                'url' => '/reports/income-expense',
                'permission' => 'reports.income-expense',
                'order' => 3,
            ],
        ];

        foreach ($children as $child) {
            $exists = DB::table('app_menus')
                ->where('parent_id', $parentMenuId)
                ->where('title', $child['title'])
                ->exists();

            if (! $exists) {
                DB::table('app_menus')->insert([
                    'id' => (string) Str::uuid(),
                    'parent_id' => $parentMenuId,
                    'title' => $child['title'],
                    'icon' => $child['icon'],
                    'url' => $child['url'],
                    'permission' => $child['permission'],
                    'order' => $child['order'],
                    'is_active' => true,
                    'section' => 'main',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $parentMenu = DB::table('app_menus')->where('title', 'Reportes')->where('section', 'main')->first();
        if ($parentMenu) {
            DB::table('app_menus')->where('parent_id', $parentMenu->id)->delete();
            DB::table('app_menus')->where('id', $parentMenu->id)->delete();
        }

        $module = DB::table('app_modules')->where('name', 'reports')->first();
        if ($module) {
            DB::table('app_permissions')->where('module_id', $module->id)->delete();
            DB::table('app_modules')->where('id', $module->id)->delete();
        }
    }
};
