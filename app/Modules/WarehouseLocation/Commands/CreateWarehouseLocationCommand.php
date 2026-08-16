<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Commands;

use App\Modules\WarehouseLocation\Requests\CreateWarehouseLocationRequest;

class CreateWarehouseLocationCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $warehouseId,
        public readonly string $name,
        public readonly string $locationCode,
        public readonly string $createdBy,
        public readonly ?string $parentId = null,
        public readonly string $type = 'shelf',
        public readonly float $capacity = 0,
        public readonly string $isDefault = 'no',
    ) {}

    public static function fromRequest(CreateWarehouseLocationRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            warehouseId: $request->string('warehouse_id')->toString(),
            name: $request->string('name')->toString(),
            locationCode: $request->string('location_code')->toString(),
            createdBy: $request->user()->id,
            parentId: $request->input('parent_id'),
            type: $request->string('type', 'shelf')->toString(),
            capacity: $request->float('capacity'),
            isDefault: $request->string('is_default', 'no')->toString(),
        );
    }
}
