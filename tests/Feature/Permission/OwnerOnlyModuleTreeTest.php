<?php

declare(strict_types=1);

use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * El módulo "companies" es exclusivo del dueño del sistema, por lo que no debe
 * ofrecerse como permiso asignable: ni en el árbol de permisos del editor de
 * roles ni en el listado plano que se otorga a los roles de acceso total.
 */
function seedModuleWithPermission(string $name, string $action): void
{
    $moduleId = (string) Str::uuid();

    DB::table('app_modules')->insert([
        'id' => $moduleId,
        'name' => $name,
        'label' => ucfirst($name),
        'is_active' => true,
        'order' => 1,
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

test('the permissions tree excludes the owner-only companies module', function () {
    seedModuleWithPermission('clients', 'clients.list');
    seedModuleWithPermission('companies', 'companies.list');

    $modules = app(PermissionRepositoryInterface::class)->getAllGroupedByModule();
    $names = collect($modules)->pluck('name')->all();

    expect($names)->toContain('clients')
        ->and($names)->not->toContain('companies');
});

test('the flat permission list excludes the owner-only companies permissions', function () {
    seedModuleWithPermission('clients', 'clients.list');
    seedModuleWithPermission('companies', 'companies.list');

    $flat = app(PermissionRepositoryInterface::class)->getAllPermissionsFlat();

    expect($flat)->toContain('clients.list')
        ->and($flat)->not->toContain('companies.list');
});
