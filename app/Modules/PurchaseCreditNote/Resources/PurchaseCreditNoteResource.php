<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseCreditNoteResource extends JsonResource
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
            'purchase_return_id' => $this->purchase_return_id,
            /** Etiqueta legible de la devolución que la origina. */
            'purchase_return_code' => $this->whenLoaded('purchaseReturn', fn () => $this->purchaseReturn?->code),
            'supplier_document_number' => $this->supplier_document_number,
            'note_date' => $this->note_date?->format('Y-m-d'),
            'reason' => $this->reason,
            'reason_detail' => $this->reason_detail,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'subtotal_ves' => $this->subtotal_ves,
            'tax_amount_ves' => $this->tax_amount_ves,
            'total_ves' => $this->total_ves,
            'applied_amount' => $this->applied_amount,
            'balance' => $this->balance,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => PurchaseCreditNoteLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
