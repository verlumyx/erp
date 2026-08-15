<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\Shared\Repositories\Contracts\UserCompanyRepositoryInterface;
use App\Modules\User\Commands\UpdateUserCommand;
use App\Modules\User\Exceptions\UserNotFoundException;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

class UserUpdateService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UserCompanyRepositoryInterface $userCompanyRepository,
    ) {}

    public function execute(string $id, UpdateUserCommand $command): User
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new UserNotFoundException;
        }

        $this->repository->update($model, $command);

        if ($command->companyId) {
            $this->userCompanyRepository->updateRole($id, $command->companyId, $command->roleId);
        }

        return $this->repository->findOrFail($id);
    }
}
