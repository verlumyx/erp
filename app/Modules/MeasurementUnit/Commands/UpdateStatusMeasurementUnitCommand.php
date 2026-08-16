<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Commands;

use App\Modules\MeasurementUnit\Requests\UpdateStatusMeasurementUnitRequest;

class UpdateStatusMeasurementUnitCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusMeasurementUnitRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
