<?php

declare(strict_types=1);

namespace App\Modules\Auth\Commands;

use App\Modules\Auth\Requests\LoginRequest;

class LoginCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $deviceName,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            deviceName: $request->string('device_name')->toString() ?: 'mobile',
        );
    }
}
