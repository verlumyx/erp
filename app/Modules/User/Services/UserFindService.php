<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Exceptions\UserNotFoundException;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

class UserFindService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function execute(string $id): User
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new UserNotFoundException;
        }

        return $model;
    }
}
