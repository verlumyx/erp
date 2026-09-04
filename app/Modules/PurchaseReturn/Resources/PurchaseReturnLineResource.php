<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_return_id' => $this->purchase_return_id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'purchase_invoice_line_id' => $this->purchase_invoice_line_id,
            'lot_id' => $this->lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            'serial_id' => $this->serial_id,
            'serial_number' => $this->whenLoaded('serial', fn () => $this->serial?->serial_number),
            'location_id' => $this->location_id,
            'location_name' => $this->whenLoaded('location', fn () => $this->location?->name),
            'quantity' => $this->quantity,
            'base_quantity' => $this->base_quantity,
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
            'reason' => $this->reason,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
