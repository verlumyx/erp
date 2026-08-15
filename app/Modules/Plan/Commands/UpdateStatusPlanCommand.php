<?php

declare(strict_types=1);

namespace App\Modules\Plan\Commands;

use App\Modules\Plan\Requests\UpdateStatusPlanRequest;

class UpdateStatusPlanCommand
{
    public function __construct(
        public readonly bool $active,
    ) {}

    public static function fromRequest(UpdateStatusPlanRequest $request): self
    {
        return new self(
            active: $request->boolean('active'),
        );
    }
}
