<?php

declare(strict_types=1);

namespace App\Modules\Client\Commands;

use App\Modules\Client\Requests\CreateClientRequest;

class CreateClientCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $createdBy,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateClientRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->toString(),
            createdBy: $request->user()->id,
            phone: $request->input('phone'),
            email: $request->input('email'),
            notes: $request->input('notes'),
        );
    }
}
