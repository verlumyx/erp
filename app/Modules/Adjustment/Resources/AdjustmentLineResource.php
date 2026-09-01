<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adjustment_id' => $this->adjustment_id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'location_id' => $this->location_id,
            'location_name' => $this->whenLoaded('location', fn () => $this->location?->name),
            'lot_id' => $this->lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            'serial_id' => $this->serial_id,
            'serial_number' => $this->whenLoaded('serial', fn () => $this->serial?->serial_number),
            /** Lo que decía el sistema al capturar, lo contado y la resta. */
            'system_quantity' => $this->system_quantity,
            'counted_quantity' => $this->counted_quantity,
            'difference_quantity' => $this->difference_quantity,
            'base_quantity' => $this->base_quantity,
            'movement_type' => $this->movement_type,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'reason' => $this->reason,
            'counted_by' => $this->counted_by,
            'counted_by_name' => $this->whenLoaded('counter', fn () => $this->counter?->name),
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
