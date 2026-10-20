<?php

declare(strict_types=1);

namespace App\Modules\Pos\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosSaleResource extends JsonResource
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
            'pos_session_id' => $this->pos_session_id,
            'pos_session_code' => $this->whenLoaded('session', fn () => $this->session?->code),
            'pos_register_id' => $this->pos_register_id,
            'pos_register_name' => $this->whenLoaded('register', fn () => $this->register?->name),
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'client_tax_number' => $this->whenLoaded('client', fn () => $this->client?->tax_number),
            'sales_invoice_id' => $this->sales_invoice_id,
            'sales_invoice_code' => $this->whenLoaded('salesInvoice', fn () => $this->salesInvoice?->code),
            'dispatch_id' => $this->dispatch_id,
            'dispatch_code' => $this->whenLoaded('dispatch', fn () => $this->dispatch?->code),
            'sale_date' => $this->sale_date?->format('Y-m-d H:i:s'),
            'currency' => $this->currency,
            'exchange_rate' => (float) $this->exchange_rate,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'igtf_amount' => (float) $this->igtf_amount,
            'total' => (float) $this->total,
            'total_in_local_currency' => (float) $this->total_in_local_currency,
            'paid_amount' => (float) $this->paid_amount,
            'change_amount_usd' => (float) $this->change_amount_usd,
            'change_amount_ves' => (float) $this->change_amount_ves,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_by_name' => $this->whenLoaded('canceller', fn () => $this->canceller?->name),
            'notes' => $this->notes,
            'status' => $this->status,
            'lines' => PosSaleLineResource::collection($this->whenLoaded('lines')),
            'payments' => PosPaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
