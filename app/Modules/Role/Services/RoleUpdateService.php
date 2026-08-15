<?php

declare(strict_types=1);

namespace App\Modules\Role\Services;

use App\Modules\Role\Commands\UpdateRoleCommand;
use App\Modules\Role\Exceptions\RoleNotFoundException;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;

class RoleUpdateService
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateRoleCommand $command): Role
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new RoleNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id);
    }
}
