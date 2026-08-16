<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Commands;

use App\Modules\MeasurementUnit\Requests\UpdateMeasurementUnitRequest;

class UpdateMeasurementUnitCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $abbreviation,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(UpdateMeasurementUnitRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            abbreviation: $request->string('abbreviation')->toString(),
            description: $request->input('description'),
        );
    }
}
