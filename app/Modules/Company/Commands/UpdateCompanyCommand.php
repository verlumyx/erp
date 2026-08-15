<?php

declare(strict_types=1);

namespace App\Modules\Company\Commands;

use App\Modules\Company\Requests\UpdateCompanyRequest;

class UpdateCompanyCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(UpdateCompanyRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            description: $request->input('description'),
        );
    }
}
