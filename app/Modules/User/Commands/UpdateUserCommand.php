<?php

declare(strict_types=1);

namespace App\Modules\User\Commands;

use App\Modules\User\Requests\UpdateUserRequest;

class UpdateUserCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $companyId = null,
        public readonly ?string $roleId = null,
        public readonly ?string $password = null,
    ) {}

    public static function fromRequest(UpdateUserRequest $request, ?string $companyId = null): self
    {
        return new self(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            companyId: $companyId,
            roleId: $request->input('role_id'),
            password: $request->filled('password') ? $request->string('password')->toString() : null,
        );
    }
}
