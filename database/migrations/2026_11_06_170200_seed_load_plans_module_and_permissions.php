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
            'name' => 'load_plans',
            'label' => 'Planes de Carga',
            'description' => 'Consolidación de despachos y carga de camiones por ruta',
            'icon' => 'ClipboardList',
            'is_active' => true,
            'order' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existingModule = DB::table('app_modules')->where('name', 'load_plans')->first();
        $targetModuleId = $existingModule ? $existingModule->id : $moduleId;

        // 2. Permissions
        $permissions = [
            ['action' => 'load-plans.list', 'label' => 'Listar planes de carga', 'order' => 1],
            ['action' => 'load-plans.create', 'label' => 'Crear planes de carga', 'order' => 2],
            ['action' => 'load-plans.show', 'label' => 'Ver detalles de planes de carga', 'order' => 3],
            ['action' => 'load-plans.update', 'label' => 'Editar planes de carga', 'order' => 4],
            ['action' => 'load-plans.update-status', 'label' => 'Cambiar estado de planes de carga', 'order' => 5],
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

        // 3. Menu in Logistics
        $parentMenu = DB::table('app_menus')
            ->where('title', 'Logística')
            ->where('section', 'main')
            ->first();

        if ($parentMenu) {
            $existingLoadPlanMenu = DB::table('app_menus')
                ->where('parent_id', $parentMenu->id)
                ->where('url', '/load-plans')
                ->first();

            if (! $existingLoadPlanMenu) {
                DB::table('app_menus')->insert([
                    'id' => (string) Str::uuid(),
                    'parent_id' => $parentMenu->id,
                    'title' => 'Planes de Carga',
                    'icon' => 'ClipboardList',
                    'url' => '/load-plans',
                    'permission' => 'load-plans.list',
                    'order' => 6,
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
        DB::table('app_menus')->where('url', '/load-plans')->delete();

        $module = DB::table('app_modules')->where('name', 'load_plans')->first();
        if ($module) {
            DB::table('app_permissions')->where('module_id', $module->id)->delete();
            DB::table('app_modules')->where('id', $module->id)->delete();
        }
    }
};
