<?php

declare(strict_types=1);

namespace App\Modules\Route\Commands;

use App\Modules\Route\Requests\UpdateStatusRouteRequest;

class UpdateStatusRouteCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusRouteRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
