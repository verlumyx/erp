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
            'name' => 'vehicles',
            'label' => 'Camiones',
            'description' => 'Gestión de flota y camiones de reparto en logística',
            'icon' => 'Truck',
            'is_active' => true,
            'order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existingModule = DB::table('app_modules')->where('name', 'vehicles')->first();
        $targetModuleId = $existingModule ? $existingModule->id : $moduleId;

        // 2. Permissions
        $permissions = [
            ['action' => 'vehicles.list', 'label' => 'Listar camiones', 'order' => 1],
            ['action' => 'vehicles.create', 'label' => 'Crear camiones', 'order' => 2],
            ['action' => 'vehicles.show', 'label' => 'Ver detalles de camiones', 'order' => 3],
            ['action' => 'vehicles.update', 'label' => 'Editar camiones', 'order' => 4],
            ['action' => 'vehicles.update-status', 'label' => 'Cambiar estado de camiones', 'order' => 5],
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
            $existingVehicleMenu = DB::table('app_menus')
                ->where('parent_id', $parentMenu->id)
                ->where('url', '/vehicles')
                ->first();

            if (! $existingVehicleMenu) {
                DB::table('app_menus')->insert([
                    'id' => (string) Str::uuid(),
                    'parent_id' => $parentMenu->id,
                    'title' => 'Camiones',
                    'icon' => 'Truck',
                    'url' => '/vehicles',
                    'permission' => 'vehicles.list',
                    'order' => 5,
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
        DB::table('app_menus')->where('url', '/vehicles')->delete();

        $module = DB::table('app_modules')->where('name', 'vehicles')->first();
        if ($module) {
            DB::table('app_permissions')->where('module_id', $module->id)->delete();
            DB::table('app_modules')->where('id', $module->id)->delete();
        }
    }
};
