<?php

declare(strict_types=1);

namespace App\Modules\Role\Services;

use App\Modules\Role\Commands\CreateRoleCommand;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;

class RoleCreateService
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function execute(CreateRoleCommand $command): Role
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
