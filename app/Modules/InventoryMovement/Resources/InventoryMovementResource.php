<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Resources;

use App\Modules\InventoryMovement\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
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
            'movement_date' => $this->movement_date?->format('Y-m-d H:i:s'),
            'type' => $this->type,
            /** Ahorra al frontend repetir la lista de tipos que cargan saldo. */
            'direction' => in_array($this->type, InventoryMovement::INBOUND_TYPES, true) ? 'in' : 'out',
            'origin_type' => $this->origin_type,
            'origin_id' => $this->origin_id,
            'origin_line_id' => $this->origin_line_id,
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
            'serial_id' => $this->serial_id,
            'serial_number' => $this->whenLoaded('serial', fn () => $this->serial?->serial_number),
            'quantity' => (float) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'total_cost' => (float) $this->total_cost,
            'balance_quantity' => (float) $this->balance_quantity,
            'balance_cost' => (float) $this->balance_cost,
            'balance_value' => (float) $this->balance_value,
            'reversal_of_id' => $this->reversal_of_id,
            'reversal_of_code' => $this->whenLoaded('reversalOf', fn () => $this->reversalOf?->code),
            'reversal_id' => $this->whenLoaded('reversal', fn () => $this->reversal?->id),
            'reversal_code' => $this->whenLoaded('reversal', fn () => $this->reversal?->code),
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
