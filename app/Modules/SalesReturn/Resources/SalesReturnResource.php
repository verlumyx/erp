<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnResource extends JsonResource
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
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'client_code' => $this->whenLoaded('client', fn () => $this->client?->code),
            'sales_invoice_id' => $this->sales_invoice_id,
            /** Etiqueta legible de la factura, para no ir a buscarla desde la pantalla. */
            'sales_invoice_code' => $this->whenLoaded('salesInvoice', fn () => $this->salesInvoice?->code),
            'sales_invoice_number' => $this->whenLoaded(
                'salesInvoice',
                fn () => $this->salesInvoice?->invoice_number,
            ),
            'dispatch_id' => $this->dispatch_id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'return_date' => $this->return_date?->format('Y-m-d'),
            'reason' => $this->reason,
            'reason_detail' => $this->reason_detail,
            'condition' => $this->condition,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'credit_note_id' => $this->credit_note_id,
            'credit_note_code' => $this->whenLoaded('creditNote', fn () => $this->creditNote?->code),
            'received_by' => $this->received_by,
            'received_by_name' => $this->whenLoaded('receiver', fn () => $this->receiver?->name),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => SalesReturnLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
