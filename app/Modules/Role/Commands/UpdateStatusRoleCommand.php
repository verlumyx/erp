<?php

declare(strict_types=1);

namespace App\Modules\Role\Commands;

use App\Modules\Role\Requests\UpdateStatusRoleRequest;

class UpdateStatusRoleCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusRoleRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
