<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemSerialResource extends JsonResource
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
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'serial_number' => $this->serial_number,
            'lot_id' => $this->lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'status' => $this->status,
            'sold_at' => $this->sold_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
