<?php

declare(strict_types=1);

namespace App\Modules\Permission\Repositories\Contracts;

use App\Modules\Permission\Commands\CreatePermissionCommand;
use App\Modules\Permission\Commands\SearchPermissionCommand;
use App\Modules\Permission\Commands\UpdatePermissionCommand;
use App\Modules\Permission\Commands\UpdateStatusPermissionCommand;
use App\Modules\Permission\Models\Permission;

interface PermissionRepositoryInterface
{
    public function create(CreatePermissionCommand $command): void;

    public function findById(string $id): ?Permission;

    public function findOrFail(string $id): Permission;

    public function update(Permission $model, UpdatePermissionCommand $command): void;

    public function updateStatus(Permission $model, UpdateStatusPermissionCommand $command): void;

    /** @return array{ data: Permission[], total: int } */
    public function search(SearchPermissionCommand $command): array;

    /** @return array<string> */
    public function getAllPermissionsFlat(): array;

    /**
     * @return array<array{id: string, name: string, label: string, icon: string|null, group: array{title: string, icon: string|null}|null, permissions: array<array{id: string, action: string, label: string}>}>
     */
    public function getAllGroupedByModule(): array;
}
