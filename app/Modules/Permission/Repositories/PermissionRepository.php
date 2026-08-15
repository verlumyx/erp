<?php

declare(strict_types=1);

namespace App\Modules\Permission\Repositories;

use App\Modules\Permission\Commands\CreatePermissionCommand;
use App\Modules\Permission\Commands\SearchPermissionCommand;
use App\Modules\Permission\Commands\UpdatePermissionCommand;
use App\Modules\Permission\Commands\UpdateStatusPermissionCommand;
use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionRepository extends PermissionFilters implements PermissionRepositoryInterface
{
    /**
     * Módulos exclusivos del dueño del sistema: su acceso no se gestiona por roles,
     * por lo que sus permisos no se listan en el árbol ni se otorgan a roles "all".
     *
     * @var array<int, string>
     */
    private const OWNER_ONLY_MODULES = ['companies'];

    public function create(CreatePermissionCommand $command): void
    {
        Permission::create([
            'id' => $command->id,
            'module_id' => $command->moduleId,
            'action' => $command->action,
            'label' => $command->label,
            'description' => $command->description,
            'is_active' => $command->isActive,
            'order' => $command->order,
        ]);
    }

    public function findById(string $id): ?Permission
    {
        return Permission::find($id);
    }

    public function findOrFail(string $id): Permission
    {
        return Permission::findOrFail($id);
    }

    public function update(Permission $model, UpdatePermissionCommand $command): void
    {
        $model->update([
            'module_id' => $command->moduleId,
            'action' => $command->action,
            'label' => $command->label,
            'description' => $command->description,
            'is_active' => $command->isActive,
            'order' => $command->order,
        ]);
    }

    public function updateStatus(Permission $model, UpdateStatusPermissionCommand $command): void
    {
        $model->update([
            'is_active' => $command->isActive,
        ]);
    }

    /**
     * @return array{ data: Permission[], total: int }
     */
    public function search(SearchPermissionCommand $command): array
    {
        $query = Permission::query();

        $query = $this->apply($query, $command->filters);

        $query->orderBy('order');

        $total = $query->count();

        $data = $query->limit($command->limit)->offset($command->offset)->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<string>
     */
    public function getAllPermissionsFlat(): array
    {
        return Permission::query()
            ->where('is_active', true)
            ->whereNotIn('module_id', $this->ownerOnlyModuleIds())
            ->orderBy('order')
            ->pluck('action')
            ->toArray();
    }

    /**
     * @return array<array{id: string, name: string, label: string, icon: string|null, permissions: array<array{id: string, action: string, label: string}>}>
     */
    public function getAllGroupedByModule(): array
    {
        $modules = \Illuminate\Support\Facades\DB::table('app_modules')
            ->where('is_active', true)
            ->whereNotIn('name', self::OWNER_ONLY_MODULES)
            ->orderBy('order')
            ->get(['id', 'name', 'label', 'icon']);

        return $modules->map(function (object $module): array {
            $permissions = Permission::query()
                ->where('module_id', $module->id)
                ->where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'action', 'label']);

            return [
                'id' => $module->id,
                'name' => $module->name,
                'label' => $module->label,
                'icon' => $module->icon,
                'permissions' => $permissions->map(fn (Permission $p): array => [
                    'id' => $p->id,
                    'action' => $p->action,
                    'label' => $p->label,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, string>
     */
    private function ownerOnlyModuleIds(): array
    {
        return \Illuminate\Support\Facades\DB::table('app_modules')
            ->whereIn('name', self::OWNER_ONLY_MODULES)
            ->pluck('id')
            ->all();
    }
}
