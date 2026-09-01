<?php

declare(strict_types=1);

namespace App\Modules\Entry\Commands;

use App\Modules\Entry\Requests\UpdateStatusEntryRequest;

class UpdateStatusEntryCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusEntryRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
