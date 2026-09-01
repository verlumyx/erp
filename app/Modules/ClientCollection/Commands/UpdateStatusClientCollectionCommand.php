<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Commands;

use App\Modules\ClientCollection\Requests\UpdateStatusClientCollectionRequest;

class UpdateStatusClientCollectionCommand
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $cancellationReason = null,
    ) {}

    public static function fromRequest(UpdateStatusClientCollectionRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            cancellationReason: $request->input('cancellation_reason'),
        );
    }
}
