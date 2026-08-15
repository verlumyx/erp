<?php

declare(strict_types=1);

namespace App\Modules\Shared\Repositories;

use App\Modules\Shared\Commands\CreateUserCompanyCommand;
use App\Modules\Shared\Models\UserCompany;
use App\Modules\Shared\Repositories\Contracts\UserCompanyRepositoryInterface;

class UserCompanyRepository implements UserCompanyRepositoryInterface
{
    public function create(CreateUserCompanyCommand $command): void
    {
        UserCompany::create([
            'id' => $command->id,
            'user_id' => $command->userId,
            'company_id' => $command->companyId,
            'role_id' => $command->roleId,
            'status' => $command->status,
            'is_default' => $command->isDefault,
        ]);
    }

    public function updateRole(string $userId, string $companyId, ?string $roleId): void
    {
        UserCompany::updateOrCreate(
            ['user_id' => $userId, 'company_id' => $companyId],
            ['role_id' => $roleId],
        );
    }
}
