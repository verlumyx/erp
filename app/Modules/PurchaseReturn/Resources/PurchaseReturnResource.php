<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
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
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'supplier_code' => $this->whenLoaded('supplier', fn () => $this->supplier?->code),
            'purchase_invoice_id' => $this->purchase_invoice_id,
            /** Etiqueta legible de la factura, para no ir a buscarla desde la pantalla. */
            'purchase_invoice_code' => $this->whenLoaded('purchaseInvoice', fn () => $this->purchaseInvoice?->code),
            'purchase_invoice_number' => $this->whenLoaded(
                'purchaseInvoice',
                fn () => $this->purchaseInvoice?->supplier_invoice_number,
            ),
            'entry_id' => $this->entry_id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'return_date' => $this->return_date?->format('Y-m-d'),
            'reason' => $this->reason,
            'reason_detail' => $this->reason_detail,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'credit_note_id' => $this->credit_note_id,
            'credit_note_code' => $this->whenLoaded('creditNote', fn () => $this->creditNote?->code),
            'carrier' => $this->carrier,
            'tracking_number' => $this->tracking_number,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => PurchaseReturnLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
