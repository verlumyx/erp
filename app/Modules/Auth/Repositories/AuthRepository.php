<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\User\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class AuthRepository implements AuthRepositoryInterface
{
    public function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    public function countTokens(User $user): int
    {
        return $user->tokens()->count();
    }

    public function createToken(User $user, string $deviceName): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
