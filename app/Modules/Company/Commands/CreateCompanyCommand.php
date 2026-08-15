<?php

declare(strict_types=1);

namespace App\Modules\Company\Commands;

use App\Modules\Company\Requests\CreateCompanyRequest;

class CreateCompanyCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $createdBy,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(CreateCompanyRequest $request): self
    {
        return new self(
            id: $request->string('id')->toString(),
            name: $request->string('name')->toString(),
            createdBy: $request->user()->id,
            description: $request->input('description'),
        );
    }
}
