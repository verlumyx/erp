<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\Shared\Commands\CreateUserCompanyCommand;
use App\Modules\Shared\Repositories\Contracts\UserCompanyRepositoryInterface;
use App\Modules\User\Commands\CreateUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Str;

class UserCreateService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UserCompanyRepositoryInterface $userCompanyRepository,
    ) {}

    public function execute(CreateUserCommand $command): User
    {
        if ($command->existingUserId) {
            $userId = $command->existingUserId;
        } else {
            $this->repository->create($command);
            $userId = $command->id;
        }

        if ($command->companyId) {
            $this->userCompanyRepository->create(new CreateUserCompanyCommand(
                id: Str::uuid7()->toString(),
                userId: $userId,
                companyId: $command->companyId,
                roleId: $command->roleId,
                status: 'active',
            ));
        }

        return $this->repository->findOrFail($userId);
    }
}
