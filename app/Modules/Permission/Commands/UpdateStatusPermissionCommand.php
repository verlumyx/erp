<?php

declare(strict_types=1);

namespace App\Modules\Permission\Commands;

use App\Modules\Permission\Requests\UpdateStatusPermissionRequest;

class UpdateStatusPermissionCommand
{
    public function __construct(
        public readonly bool $isActive,
    ) {}

    public static function fromRequest(UpdateStatusPermissionRequest $request): self
    {
        return new self(
            isActive: $request->boolean('is_active'),
        );
    }
}
