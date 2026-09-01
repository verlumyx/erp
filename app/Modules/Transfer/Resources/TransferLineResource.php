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
            'origin_location_id' => $this->origin_location_id,
            'origin_location_name' => $this->whenLoaded('originLocation', fn () => $this->originLocation?->name),
            'destination_location_id' => $this->destination_location_id,
            'destination_location_name' => $this->whenLoaded('destinationLocation', fn () => $this->destinationLocation?->name),
            'lot_id' => $this->lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            'serial_id' => $this->serial_id,
            'serial_number' => $this->whenLoaded('serial', fn () => $this->serial?->serial_number),
            'quantity' => $this->quantity,
            'base_quantity' => $this->base_quantity,
            /** El traslado no pone precio: estos importes son el costo que viaja. */
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'sent_quantity' => $this->sent_quantity,
            'received_quantity' => $this->received_quantity,
            'difference_quantity' => $this->difference_quantity,
            /** Costo con el que viaja: en borrador el promedio, confirmado el real. */
            'unit_cost' => $this->unit_cost,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
