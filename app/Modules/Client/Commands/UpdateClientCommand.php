<?php

declare(strict_types=1);

namespace App\Modules\Client\Commands;

use App\Modules\Client\Requests\UpdateClientRequest;

class UpdateClientCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateClientRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            phone: $request->input('phone'),
            email: $request->input('email'),
            notes: $request->input('notes'),
        );
    }
}
