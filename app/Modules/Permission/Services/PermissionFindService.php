<?php

declare(strict_types=1);

namespace App\Modules\Permission\Services;

use App\Modules\Permission\Exceptions\PermissionNotFoundException;
use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionFindService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $repository,
    ) {}

    public function execute(string $id): Permission
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new PermissionNotFoundException;
        }

        return $model;
    }
}
