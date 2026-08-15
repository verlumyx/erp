<?php

declare(strict_types=1);

namespace App\Modules\Service\Commands;

use App\Modules\Service\Requests\UpdateServiceRequest;

class UpdateServiceCommand
{
    public function __construct(
        public readonly string $name,
        public readonly int $maxProfiles,
        public readonly ?string $logoUrl = null,
    ) {}

    public static function fromRequest(UpdateServiceRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            maxProfiles: $request->integer('max_profiles'),
            logoUrl: $request->input('logo_url'),
        );
    }
}
