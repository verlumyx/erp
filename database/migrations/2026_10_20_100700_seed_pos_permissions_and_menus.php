<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = (string) Str::uuid();

        // 1. Insert module
        DB::table('app_modules')->insertOrIgnore([
            'id' => $moduleId,
            'name' => 'pos',
            'label' => 'Punto de Venta',
            'description' => 'Facturación rápida, cobro y cortes de caja',
            'icon' => 'Store',
            'is_active' => true,
            'order' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existingModule = DB::table('app_modules')->where('name', 'pos')->first();
        $targetModuleId = $existingModule ? $existingModule->id : $moduleId;

        // 2. Insert permissions
        $permissions = [
            ['action' => 'pos.access', 'label' => 'Acceder al terminal de venta rápida (POS)', 'order' => 1],
            ['action' => 'pos-registers.list', 'label' => 'Listar cajas registradoras', 'order' => 2],
            ['action' => 'pos-registers.create', 'label' => 'Crear cajas registradoras', 'order' => 3],
            ['action' => 'pos-registers.edit', 'label' => 'Editar cajas registradoras', 'order' => 4],
            ['action' => 'pos-registers.status', 'label' => 'Cambiar estado de cajas registradoras', 'order' => 5],
            ['action' => 'pos-sessions.list', 'label' => 'Listar sesiones y cortes de caja', 'order' => 6],
            ['action' => 'pos-sessions.open', 'label' => 'Abrir turno de caja', 'order' => 7],
            ['action' => 'pos-sessions.close', 'label' => 'Cerrar turno y realizar corte de caja', 'order' => 8],
            ['action' => 'pos-sessions.read', 'label' => 'Ver detalles y reporte Z de corte de caja', 'order' => 9],
            ['action' => 'pos-sales.create', 'label' => 'Procesar ventas en POS', 'order' => 10],
            ['action' => 'pos-sales.cancel', 'label' => 'Anular ventas en POS', 'order' => 11],
            ['action' => 'pos-cash-movements.create', 'label' => 'Registrar entradas y retiros de efectivo', 'order' => 12],
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
    }

    public function down(): void
    {
        $module = DB::table('app_modules')->where('name', 'pos')->first();

        if ($module) {
            DB::table('app_permissions')->where('module_id', $module->id)->delete();
            DB::table('app_modules')->where('id', $module->id)->delete();
        }
    }
};
