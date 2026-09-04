<?php

declare(strict_types=1);

namespace App\Modules\Import\Commands;

use App\Modules\Import\Requests\UpdateStatusImportRequest;

class UpdateStatusImportCommand
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $cancellationReason = null,
    ) {}

    public static function fromRequest(UpdateStatusImportRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            cancellationReason: $request->input('cancellation_reason'),
        );
    }
}
