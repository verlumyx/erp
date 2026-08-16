<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Commands;

use App\Modules\MeasurementUnit\Requests\CreateMeasurementUnitRequest;

class CreateMeasurementUnitCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $name,
        public readonly string $abbreviation,
        public readonly string $createdBy,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(CreateMeasurementUnitRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            name: $request->string('name')->toString(),
            abbreviation: $request->string('abbreviation')->toString(),
            createdBy: $request->user()->id,
            description: $request->input('description'),
        );
    }
}
