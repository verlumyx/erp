<?php

declare(strict_types=1);

namespace App\Modules\Plan\Commands;

use App\Modules\Plan\Requests\UpdatePlanRequest;

class UpdatePlanCommand
{
    public function __construct(
        public readonly string $serviceId,
        public readonly string $name,
        public readonly string $capacity,
        public readonly int $durationDays,
        public readonly float $salePrice,
        public readonly float $roiTargetPct,
    ) {}

    public static function fromRequest(UpdatePlanRequest $request): self
    {
        return new self(
            serviceId: $request->string('service_id')->toString(),
            name: $request->string('name')->toString(),
            capacity: $request->string('capacity')->toString(),
            durationDays: $request->integer('duration_days'),
            salePrice: (float) $request->input('sale_price'),
            roiTargetPct: (float) $request->input('roi_target_pct'),
        );
    }
}
