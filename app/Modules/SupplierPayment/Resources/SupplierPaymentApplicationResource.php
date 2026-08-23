<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierPaymentApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_invoice_id' => $this->purchase_invoice_id,
            'purchase_invoice_code' => $this->whenLoaded('purchaseInvoice', fn () => $this->purchaseInvoice?->code),
            'supplier_invoice_number' => $this->whenLoaded(
                'purchaseInvoice',
                fn () => $this->purchaseInvoice?->supplier_invoice_number,
            ),
            'invoice_due_date' => $this->whenLoaded(
                'purchaseInvoice',
                fn () => $this->purchaseInvoice?->due_date?->format('Y-m-d'),
            ),
            'invoice_total' => $this->whenLoaded('purchaseInvoice', fn () => $this->purchaseInvoice?->total),
            'invoice_balance' => $this->whenLoaded('purchaseInvoice', fn () => $this->purchaseInvoice?->balance),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'applied_amount' => $this->applied_amount,
            'applied_at' => $this->applied_at?->format('Y-m-d H:i:s'),
            'exchange_rate' => $this->exchange_rate,
            'exchange_difference' => $this->exchange_difference,
            'status' => $this->status,
        ];
    }
}
