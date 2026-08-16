<?php

declare(strict_types=1);

namespace App\Modules\Item\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'sale_tax_id' => $this->sale_tax_id,
            'purchase_tax_id' => $this->purchase_tax_id,
            'cost_method' => $this->cost_method,
            'standard_cost' => $this->standard_cost,
            'average_cost' => $this->average_cost,
            'min_price' => $this->min_price,
            'is_purchasable' => $this->is_purchasable,
            'is_sellable' => $this->is_sellable,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'reorder_quantity' => $this->reorder_quantity,
            'weight' => $this->weight,
            'volume' => $this->volume,
            'image_path' => $this->image_path,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'units' => $this->whenLoaded(
                'units',
                fn (): array => ItemUnitResource::collection($this->units)->resolve($request),
            ),
            'prices' => $this->whenLoaded(
                'prices',
                fn (): array => ItemPriceResource::collection($this->prices)->resolve($request),
            ),
        ];
    }
}
