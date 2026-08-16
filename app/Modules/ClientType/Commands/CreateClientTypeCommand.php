<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Commands;

use App\Modules\ClientType\Requests\CreateClientTypeRequest;

class CreateClientTypeCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $createdBy,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(CreateClientTypeRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->toString(),
            createdBy: $request->user()->id,
            description: $request->input('description'),
        );
    }
}
