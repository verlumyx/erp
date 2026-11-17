<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Commands;

use App\Modules\LoadPlan\Requests\UpdateStatusLoadPlanRequest;

class UpdateStatusLoadPlanCommand
{
    /**
     * @param  array<int, string>|null  $deliveredDispatchIds
     */
    public function __construct(
        public readonly string $status,
        public readonly ?array $deliveredDispatchIds = null,
    ) {}

    public static function fromRequest(UpdateStatusLoadPlanRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            deliveredDispatchIds: $request->has('delivered_dispatch_ids')
                ? (array) $request->input('delivered_dispatch_ids')
                : null,
        );
    }
}
