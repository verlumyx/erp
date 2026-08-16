<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Commands;

use App\Modules\WarehouseLocation\Requests\UpdateWarehouseLocationRequest;

class UpdateWarehouseLocationCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $locationCode,
        public readonly ?string $parentId = null,
        public readonly string $type = 'shelf',
        public readonly float $capacity = 0,
        public readonly string $isDefault = 'no',
    ) {}

    public static function fromRequest(UpdateWarehouseLocationRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            locationCode: $request->string('location_code')->toString(),
            parentId: $request->input('parent_id'),
            type: $request->string('type', 'shelf')->toString(),
            capacity: $request->float('capacity'),
            isDefault: $request->string('is_default', 'no')->toString(),
        );
    }
}
