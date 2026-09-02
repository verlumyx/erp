<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_id' => $this->transfer_id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'quantity' => $this->quantity,
            'base_quantity' => $this->base_quantity,
            /** El traslado no pone precio: estos importes son el costo que viaja. */
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            /** Costo con el que viaja: en borrador el promedio, confirmado el real. */
            'unit_cost' => $this->unit_cost,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
