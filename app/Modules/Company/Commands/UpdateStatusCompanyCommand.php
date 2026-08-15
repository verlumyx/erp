<?php

declare(strict_types=1);

namespace App\Modules\Company\Commands;

use App\Modules\Company\Requests\UpdateStatusCompanyRequest;

class UpdateStatusCompanyCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusCompanyRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
