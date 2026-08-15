<?php

declare(strict_types=1);

namespace App\Modules\Client\Commands;

use App\Modules\Client\Requests\UpdateStatusClientRequest;

class UpdateStatusClientCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusClientRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
