<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Commands;

use App\Modules\ClientType\Requests\UpdateStatusClientTypeRequest;

class UpdateStatusClientTypeCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusClientTypeRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
