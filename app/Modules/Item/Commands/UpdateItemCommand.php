<?php

declare(strict_types=1);

namespace App\Modules\Item\Commands;

use App\Modules\Item\Requests\UpdateItemRequest;

class UpdateItemCommand
{
    /**
     * @param  array<int, ItemUnitData>  $units
     * @param  array<int, ItemPriceData>  $prices
     */
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly array $units = [],
        public readonly array $prices = [],
        public readonly ?string $barcode = null,
        public readonly ?string $description = null,
        public readonly string $type = 'inventoried',
        public readonly ?string $categoryId = null,
        public readonly string $costMethod = 'average',
        public readonly string $standardCost = '0',
        public readonly string $minPrice = '0',
        public readonly string $isPurchasable = 'yes',
        public readonly string $isSellable = 'yes',
        public readonly string $minStock = '0',
        public readonly string $maxStock = '0',
        public readonly string $reorderQuantity = '0',
        public readonly string $weight = '0',
        public readonly string $volume = '0',
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateItemRequest $request): self
    {
        return new self(
            sku: $request->string('sku')->toString(),
            name: $request->string('name')->toString(),
            units: ItemUnitData::collection($request->input('units', [])),
            prices: ItemPriceData::collection($request->input('prices', [])),
            barcode: $request->input('barcode'),
            description: $request->input('description'),
            type: $request->string('type', 'inventoried')->toString(),
            categoryId: $request->input('category_id'),
            costMethod: $request->string('cost_method', 'average')->toString(),
            standardCost: (string) $request->input('standard_cost', 0),
            minPrice: (string) $request->input('min_price', 0),
            isPurchasable: $request->string('is_purchasable', 'yes')->toString(),
            isSellable: $request->string('is_sellable', 'yes')->toString(),
            minStock: (string) $request->input('min_stock', 0),
            maxStock: (string) $request->input('max_stock', 0),
            reorderQuantity: (string) $request->input('reorder_quantity', 0),
            weight: (string) $request->input('weight', 0),
            volume: (string) $request->input('volume', 0),
            notes: $request->input('notes'),
        );
    }
}
