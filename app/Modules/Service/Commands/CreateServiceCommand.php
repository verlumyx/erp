<?php

declare(strict_types=1);

namespace App\Modules\Service\Commands;

use App\Modules\Service\Requests\CreateServiceRequest;

class CreateServiceCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly int $maxProfiles,
        public readonly ?string $logoUrl = null,
    ) {}

    public static function fromRequest(CreateServiceRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->toString(),
            maxProfiles: $request->integer('max_profiles'),
            logoUrl: $request->input('logo_url'),
        );
    }
}
