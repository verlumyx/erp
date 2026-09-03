<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\UpdateStatusStoreItemRequest;

class UpdateStatusStoreItemCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusStoreItemRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
