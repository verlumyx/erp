<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sales_invoice_id' => $this->sales_invoice_id,
            'line_number' => $this->line_number,
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_sku' => $this->whenLoaded('item', fn () => $this->item?->sku),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'warehouse_id' => $this->warehouse_id,
            'lot_id' => $this->lot_id,
            'serial_id' => $this->serial_id,
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
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'margin_amount' => $this->margin_amount,
            'returned_quantity' => $this->returned_quantity,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
