<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientCollectionApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sales_invoice_id' => $this->sales_invoice_id,
            'sales_invoice_code' => $this->whenLoaded('salesInvoice', fn () => $this->salesInvoice?->code),
            'invoice_number' => $this->whenLoaded('salesInvoice', fn () => $this->salesInvoice?->invoice_number),
            'invoice_due_date' => $this->whenLoaded(
                'salesInvoice',
                fn () => $this->salesInvoice?->due_date?->format('Y-m-d'),
            ),
            'invoice_total' => $this->whenLoaded('salesInvoice', fn () => $this->salesInvoice?->total),
            'invoice_balance' => $this->whenLoaded('salesInvoice', fn () => $this->salesInvoice?->balance),
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
