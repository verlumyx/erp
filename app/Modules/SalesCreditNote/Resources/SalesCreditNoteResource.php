<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesCreditNoteResource extends JsonResource
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
            'sales_invoice_number' => $this->whenLoaded('salesInvoice', fn () => $this->salesInvoice?->invoice_number),
            'sales_return_id' => $this->sales_return_id,
            'note_series' => $this->note_series,
            /** Correlativo fiscal: nulo mientras la nota es borrador. */
            'note_number' => $this->note_number,
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
            'fiscal_status' => $this->fiscal_status,
            'fiscal_uuid' => $this->fiscal_uuid,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => SalesCreditNoteLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
