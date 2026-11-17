<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Commands;

use App\Modules\LoadPlan\Requests\UpdateStatusLoadPlanRequest;

class UpdateStatusLoadPlanCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusLoadPlanRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
