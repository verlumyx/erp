<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

use App\Modules\Dispatch\Requests\UpdateStatusDispatchRequest;

class UpdateStatusDispatchCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusDispatchRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
