<?php

declare(strict_types=1);

namespace App\Modules\Tax\Commands;

use App\Modules\Tax\Requests\UpdateStatusTaxRequest;

class UpdateStatusTaxCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusTaxRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
