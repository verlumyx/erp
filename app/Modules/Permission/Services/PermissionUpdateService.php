<?php

declare(strict_types=1);

namespace App\Modules\Permission\Services;

use App\Modules\Permission\Commands\UpdatePermissionCommand;
use App\Modules\Permission\Exceptions\PermissionNotFoundException;
use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionUpdateService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdatePermissionCommand $command): Permission
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new PermissionNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id);
    }
}
