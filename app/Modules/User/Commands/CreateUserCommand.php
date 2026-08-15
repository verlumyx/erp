<?php

declare(strict_types=1);

namespace App\Modules\User\Commands;

use App\Modules\User\Requests\CreateUserRequest;

class CreateUserCommand
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name,
        public readonly string $email,
        public readonly ?string $password,
        public readonly ?string $companyId = null,
        public readonly ?string $roleId = null,
        public readonly ?string $existingUserId = null,
    ) {}

    public static function fromRequest(CreateUserRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            name: $request->filled('name') ? $request->string('name')->toString() : null,
            email: $request->string('email')->toString(),
            password: $request->filled('password') ? $request->string('password')->toString() : null,
            companyId: $companyId,
            roleId: $request->input('role_id'),
            existingUserId: $request->input('existing_user_id'),
        );
    }
}
