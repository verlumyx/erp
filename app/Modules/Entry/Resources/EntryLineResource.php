<?php

declare(strict_types=1);

namespace App\Modules\Entry\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_id' => $this->entry_id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            'location_id' => $this->location_id,
            'location_name' => $this->whenLoaded('location', fn () => $this->location?->name),
            'lot_number' => $this->lot_number,
            'lot_id' => $this->lot_id,
            'lot_code' => $this->whenLoaded('lot', fn () => $this->lot?->code),
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'serial_numbers' => $this->serial_numbers ?? [],
            'quantity' => $this->quantity,
            'base_quantity' => $this->base_quantity,
            'received_quantity' => $this->received_quantity,
            'rejected_quantity' => $this->rejected_quantity,
            'unit_price' => $this->unit_price,
            'discount_percent' => $this->discount_percent,
            'discount_amount' => $this->discount_amount,
            'tax_id' => $this->tax_id,
            'tax_percent' => $this->tax_percent,
            'tax_amount' => $this->tax_amount,
            'withholding_percent' => $this->withholding_percent,
            'withholding_amount' => $this->withholding_amount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            /** Costo por unidad base: antes y después de prorratear los gastos. */
            'unit_cost' => $this->unit_cost,
            'landed_cost' => $this->landed_cost,
            'rejection_reason' => $this->rejection_reason,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
