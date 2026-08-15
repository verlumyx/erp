<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Commands\LoginCommand;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Exceptions\MaxDevicesReachedException;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    public const MAX_DEVICES = 2;

    public function __construct(
        private readonly AuthRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ user: User, token: string }
     *
     * @throws InvalidCredentialsException
     * @throws MaxDevicesReachedException
     */
    public function execute(LoginCommand $command): array
    {
        $user = $this->repository->findUserByEmail($command->email);

        if ($user === null || ! Hash::check($command->password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        if ($this->repository->countTokens($user) >= self::MAX_DEVICES) {
            throw new MaxDevicesReachedException;
        }

        $token = $this->repository->createToken($user, $command->deviceName);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
