<?php

declare(strict_types=1);

namespace App\Modules\User\Commands;

use App\Modules\User\Requests\UpdateStatusUserRequest;

class UpdateStatusUserCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusUserRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
