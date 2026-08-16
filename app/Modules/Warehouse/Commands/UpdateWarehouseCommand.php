<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Commands;

use App\Modules\Warehouse\Requests\UpdateWarehouseRequest;

class UpdateWarehouseCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $type = 'main',
        public readonly ?string $address = null,
        public readonly ?string $phone = null,
        public readonly ?string $city = null,
        public readonly ?string $responsibleUserId = null,
        public readonly string $isDefault = 'no',
        public readonly string $allowsNegativeStock = 'no',
        public readonly string $usesLocations = 'no',
        public readonly string $isSalesAvailable = 'yes',
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateWarehouseRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            type: $request->string('type', 'main')->toString(),
            address: $request->input('address'),
            phone: $request->input('phone'),
            city: $request->input('city'),
            responsibleUserId: $request->input('responsible_user_id'),
            isDefault: $request->string('is_default', 'no')->toString(),
            allowsNegativeStock: $request->string('allows_negative_stock', 'no')->toString(),
            usesLocations: $request->string('uses_locations', 'no')->toString(),
            isSalesAvailable: $request->string('is_sales_available', 'yes')->toString(),
            notes: $request->input('notes'),
        );
    }
}
