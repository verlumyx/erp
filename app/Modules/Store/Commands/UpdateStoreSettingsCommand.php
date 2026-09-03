<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\UpdateStoreSettingsRequest;

class UpdateStoreSettingsCommand
{
    public function __construct(
        public readonly string $isEnabled,
        public readonly string $storeName,
        public readonly string $brandColor,
        public readonly ?string $priceListId,
        public readonly ?string $warehouseId,
        public readonly string $showsStock,
        public readonly string $allowsOrders,
        public readonly ?string $defaultClientTypeId,
        public readonly string $showsSecondaryCurrency,
        public readonly ?string $contactPhone,
        public readonly ?string $contactEmail,
        public readonly ?string $storeUrl,
    ) {}

    public static function fromRequest(UpdateStoreSettingsRequest $request): self
    {
        $nullable = static fn (mixed $value): ?string => $value === null || $value === '' ? null : (string) $value;

        return new self(
            isEnabled: $request->string('is_enabled', 'no')->toString(),
            storeName: $request->string('store_name')->toString(),
            brandColor: strtolower($request->string('brand_color', '#111827')->toString()),
            priceListId: $nullable($request->input('price_list_id')),
            warehouseId: $nullable($request->input('warehouse_id')),
            showsStock: $request->string('shows_stock', 'no')->toString(),
            allowsOrders: $request->string('allows_orders', 'no')->toString(),
            defaultClientTypeId: $nullable($request->input('default_client_type_id')),
            showsSecondaryCurrency: $request->string('shows_secondary_currency', 'yes')->toString(),
            contactPhone: $nullable($request->input('contact_phone')),
            contactEmail: $nullable($request->input('contact_email')),
            storeUrl: $nullable($request->input('store_url')),
        );
    }
}
