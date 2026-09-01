<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesCreditNoteLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sales_credit_note_id' => $this->sales_credit_note_id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'sales_invoice_line_id' => $this->sales_invoice_line_id,
            'warehouse_id' => $this->warehouse_id,
            'lot_id' => $this->lot_id,
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
            /** Costo con el que la mercancía reingresa: el mismo de la venta. */
            'unit_cost' => $this->unit_cost,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
