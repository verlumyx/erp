<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'item_sku' => $this->whenLoaded('item', fn () => $this->item?->sku),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'location_id' => $this->location_id,
            'location_name' => $this->whenLoaded('location', fn () => $this->location?->name),
            'location_code' => $this->whenLoaded('location', fn () => $this->location?->location_code),
            'lot_id' => $this->lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            'quantity' => (float) $this->quantity,
            'reserved_quantity' => (float) $this->reserved_quantity,
            'incoming_quantity' => (float) $this->incoming_quantity,
            'available_quantity' => (float) $this->available_quantity,
            'average_cost' => (float) $this->average_cost,
            'total_value' => (float) $this->total_value,
            'last_movement_at' => $this->last_movement_at?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
