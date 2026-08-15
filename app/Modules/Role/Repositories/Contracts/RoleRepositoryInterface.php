<?php

declare(strict_types=1);

namespace App\Modules\Role\Repositories\Contracts;

use App\Modules\Role\Commands\CreateRoleCommand;
use App\Modules\Role\Commands\SearchRoleCommand;
use App\Modules\Role\Commands\UpdateRoleCommand;
use App\Modules\Role\Commands\UpdateStatusRoleCommand;
use App\Modules\Role\Models\Role;

interface RoleRepositoryInterface
{
    public function create(CreateRoleCommand $command): void;

    public function findById(string $id): ?Role;

    public function findOrFail(string $id): Role;

    public function update(Role $model, UpdateRoleCommand $command): void;

    public function updateStatus(Role $model, UpdateStatusRoleCommand $command): void;

    /** @return array{ data: Role[], total: int } */
    public function search(SearchRoleCommand $command): array;

    /** @return array<array{id: string, name: string, description: string}> */
    public function getActive(?string $companyId = null): array;
}
