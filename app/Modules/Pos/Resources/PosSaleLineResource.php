<?php

declare(strict_types=1);

namespace App\Modules\Pos\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSaleLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pos_sale_id' => $this->pos_sale_id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_barcode' => $this->whenLoaded('item', fn () => $this->item?->barcode),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'measurement_unit_symbol' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->symbol),
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount_percent' => (float) $this->discount_percent,
            'discount_amount' => (float) $this->discount_amount,
            'tax_id' => $this->tax_id,
            'tax_percent' => (float) $this->tax_percent,
            'tax_amount' => (float) $this->tax_amount,
            'subtotal' => (float) $this->subtotal,
            'total' => (float) $this->total,
            'status' => $this->status,
        ];
    }
}
