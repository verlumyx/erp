<?php

declare(strict_types=1);

namespace App\Modules\Role\Services;

use App\Modules\Role\Exceptions\RoleNotFoundException;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;

class RoleFindService
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(string $id): Role
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new RoleNotFoundException;
        }

        return $model;
    }
}
