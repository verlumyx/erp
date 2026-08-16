<?php

declare(strict_types=1);

namespace App\Modules\Item\Commands;

use App\Modules\Item\Requests\UpdateStatusItemRequest;

class UpdateStatusItemCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusItemRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
