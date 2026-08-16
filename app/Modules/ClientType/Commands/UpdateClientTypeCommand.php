<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Commands;

use App\Modules\ClientType\Requests\UpdateClientTypeRequest;

class UpdateClientTypeCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(UpdateClientTypeRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            description: $request->input('description'),
        );
    }
}
