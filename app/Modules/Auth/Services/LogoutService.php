<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\User\Models\User;

class LogoutService
{
    public function __construct(
        private readonly AuthRepositoryInterface $repository,
    ) {}

    public function execute(User $user): void
    {
        $this->repository->revokeCurrentToken($user);
    }
}
