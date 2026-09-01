<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Commands;

use App\Modules\ClientAdvance\Requests\UpdateStatusClientAdvanceRequest;

class UpdateStatusClientAdvanceCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusClientAdvanceRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
