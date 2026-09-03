<?php

declare(strict_types=1);

namespace App\Modules\Permission\Repositories;

use App\Modules\Permission\Commands\CreatePermissionCommand;
use App\Modules\Permission\Commands\SearchPermissionCommand;
use App\Modules\Permission\Commands\UpdatePermissionCommand;
use App\Modules\Permission\Commands\UpdateStatusPermissionCommand;
use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;
use App\Modules\Shared\Repositories\Contracts\CompanyDisabledMenuRepositoryInterface;

class PermissionRepository extends PermissionFilters implements PermissionRepositoryInterface
{
    public function __construct(
        private readonly CompanyDisabledMenuRepositoryInterface $disabledMenus,
    ) {}

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
    public function getAllPermissionsFlat(?string $companyId = null): array
    {
        return Permission::query()
            ->where('is_active', true)
            ->whereNotIn('module_id', $this->excludedModuleIds($companyId))
            ->orderBy('order')
            ->pluck('action')
            ->toArray();
    }

    /**
     * Los módulos salen ordenados como el sidebar: primero por el grupo del menú
     * al que pertenecen y, dentro del grupo, en el orden en que se listan ahí.
     * Los que no cuelgan de ningún grupo (los del pie de página) quedan al final.
     *
     * @return array<array{id: string, name: string, label: string, icon: string|null, group: array{title: string, icon: string|null}|null, permissions: array<array{id: string, action: string, label: string}>}>
     */
    public function getAllGroupedByModule(?string $companyId = null): array
    {
        $menuGroups = $this->menuGroupsByModule();

        $modules = \Illuminate\Support\Facades\DB::table('app_modules')
            ->where('is_active', true)
            ->whereNotIn('name', $this->excludedModuleNames($companyId))
            ->orderBy('order')
            ->get(['id', 'name', 'label', 'icon', 'order']);

        return $modules
            ->sort(function (object $first, object $second) use ($menuGroups): int {
                $firstGroup = $menuGroups[$first->name]['group_order'] ?? PHP_INT_MAX;
                $secondGroup = $menuGroups[$second->name]['group_order'] ?? PHP_INT_MAX;

                if ($firstGroup !== $secondGroup) {
                    return $firstGroup <=> $secondGroup;
                }

                return ($menuGroups[$first->name]['item_order'] ?? $first->order)
                    <=> ($menuGroups[$second->name]['item_order'] ?? $second->order);
            })
            ->map(function (object $module) use ($menuGroups): array {
                $permissions = Permission::query()
                    ->where('module_id', $module->id)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->get(['id', 'action', 'label']);

                $group = $menuGroups[$module->name] ?? null;

                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'label' => $module->label,
                    'icon' => $module->icon,
                    'group' => $group === null ? null : [
                        'title' => $group['title'],
                        'icon' => $group['icon'],
                    ],
                    'permissions' => $permissions->map(fn (Permission $p): array => [
                        'id' => $p->id,
                        'action' => $p->action,
                        'label' => $p->label,
                    ])->values()->all(),
                ];
            })->values()->all();
    }

    /**
     * El menú del sidebar es la única fuente de verdad de a qué grupo pertenece
     * cada módulo: un ítem con permiso 'purchase-orders.list' colgado de
     * 'Compras' deja el módulo 'purchase-orders' dentro de ese grupo. Un módulo
     * sin padre —los del pie de página— no aparece aquí y se muestra suelto.
     *
     * @return array<string, array{title: string, icon: string|null, group_order: int, item_order: int}>
     */
    private function menuGroupsByModule(): array
    {
        $items = \Illuminate\Support\Facades\DB::table('app_menus as item')
            ->join('app_menus as parent', 'item.parent_id', '=', 'parent.id')
            ->where('item.is_active', true)
            ->where('parent.is_active', true)
            ->whereNotNull('item.permission')
            ->orderBy('parent.order')
            ->orderBy('item.order')
            ->get([
                'item.permission',
                'item.order as item_order',
                'parent.title',
                'parent.icon',
                'parent.order as group_order',
            ]);

        $groups = [];

        foreach ($items as $item) {
            $module = \Illuminate\Support\Str::before($item->permission, '.');

            if (isset($groups[$module])) {
                continue;
            }

            $groups[$module] = [
                'title' => $item->title,
                'icon' => $item->icon,
                'group_order' => (int) $item->group_order,
                'item_order' => (int) $item->item_order,
            ];
        }

        return $groups;
    }

    /**
     * Los del dueño del sistema más, si hay empresa, los que esa empresa no ve
     * en su menú: un permiso de un módulo escondido no tiene sentido en un rol.
     *
     * @return array<int, string>
     */
    private function excludedModuleNames(?string $companyId): array
    {
        if ($companyId === null) {
            return self::OWNER_ONLY_MODULES;
        }

        return array_values(array_unique([
            ...self::OWNER_ONLY_MODULES,
            ...$this->disabledMenus->disabledModuleNames($companyId),
        ]));
    }

    /**
     * @return array<int, string>
     */
    private function excludedModuleIds(?string $companyId): array
    {
        return \Illuminate\Support\Facades\DB::table('app_modules')
            ->whereIn('name', $this->excludedModuleNames($companyId))
            ->pluck('id')
            ->all();
    }
}
