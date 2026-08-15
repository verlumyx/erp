<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories\Contracts;

use App\Modules\User\Models\User;

interface AuthRepositoryInterface
{
    public function findUserByEmail(string $email): ?User;

    public function countTokens(User $user): int;

    public function createToken(User $user, string $deviceName): string;

    public function revokeCurrentToken(User $user): void;
}
