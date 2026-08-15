<?php

declare(strict_types=1);

namespace App\Modules\Shared\Repositories\Contracts;

use App\Modules\Shared\Commands\CreateUserCompanyCommand;

interface UserCompanyRepositoryInterface
{
    public function create(CreateUserCompanyCommand $command): void;

    public function updateRole(string $userId, string $companyId, ?string $roleId): void;
}
