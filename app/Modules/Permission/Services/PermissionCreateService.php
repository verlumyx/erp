<?php

declare(strict_types=1);

namespace App\Modules\Permission\Services;

use App\Modules\Permission\Commands\CreatePermissionCommand;
use App\Modules\Permission\Models\Permission;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionCreateService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $repository,
    ) {}

    public function execute(CreatePermissionCommand $command): Permission
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
