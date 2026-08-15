<?php

declare(strict_types=1);

namespace App\Modules\Role\Repositories;

use App\Modules\Role\Commands\CreateRoleCommand;
use App\Modules\Role\Commands\SearchRoleCommand;
use App\Modules\Role\Commands\UpdateRoleCommand;
use App\Modules\Role\Commands\UpdateStatusRoleCommand;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Models\RolePermission;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;

class RoleRepository extends RoleFilters implements RoleRepositoryInterface
{
    public function create(CreateRoleCommand $command): void
    {
        Role::create([
            'id' => $command->id,
            'company_id' => $command->companyId,
            'name' => $command->name,
            'description' => $command->description,
            'permission_type' => $command->permissionType,
            'status' => 'active',
        ]);

        if ($command->permissionType === 'custom' && count($command->permissions) > 0) {
            foreach ($command->permissions as $permission) {
                RolePermission::create([
                    'role_id' => $command->id,
                    'permission' => $permission,
                ]);
            }
        }
    }

    public function findById(string $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    public function findOrFail(string $id): Role
    {
        return Role::with('permissions')->findOrFail($id);
    }

    public function update(Role $model, UpdateRoleCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'description' => $command->description,
            'permission_type' => $command->permissionType,
        ]);

        $model->permissions()->delete();

        if ($command->permissionType === 'custom' && count($command->permissions) > 0) {
            foreach ($command->permissions as $permission) {
                RolePermission::create([
                    'role_id' => $model->id,
                    'permission' => $permission,
                ]);
            }
        }
    }

    public function updateStatus(Role $model, UpdateStatusRoleCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array<array{id: string, name: string, description: string}>
     */
    public function getActive(?string $companyId = null): array
    {
        return Role::query()
            ->where('status', 'active')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{ data: Role[], total: int }
     */
    public function search(SearchRoleCommand $command): array
    {
        $query = Role::query()->with('permissions')
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->limit($command->limit)->offset($command->offset)->get();

        return ['data' => $data->all(), 'total' => $total];
    }
}
