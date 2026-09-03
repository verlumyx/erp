<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreOrderLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item', fn (): ?array => $this->item === null ? null : [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'sku' => $this->item->sku,
                'name' => $this->item->name,
            ]),
            'store_item_id' => $this->store_item_id,
            'store_item' => $this->whenLoaded('storeItem', fn (): ?array => $this->storeItem === null ? null : [
                'id' => $this->storeItem->id,
                'slug' => $this->storeItem->slug,
                'title' => $this->storeItem->title,
            ]),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit' => $this->whenLoaded('measurementUnit', fn (): ?array => $this->measurementUnit === null ? null : [
                'id' => $this->measurementUnit->id,
                'name' => $this->measurementUnit->name,
                'abbreviation' => $this->measurementUnit->abbreviation,
            ]),
            'quantity' => (string) $this->quantity,
            'unit_price' => (string) $this->unit_price,
            'list_price' => (string) $this->list_price,
            'subtotal' => (string) $this->subtotal,
            'total' => (string) $this->total,
            'status' => $this->status,
        ];
    }
}
