<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Resources;

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceResource extends JsonResource
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
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            /** Etiqueta legible del documento origen, para no ir a buscarlo desde la pantalla. */
            'sourceable_code' => $this->whenLoaded('sourceable', fn (): ?string => $this->sourceable instanceof PurchaseOrder
                ? $this->sourceable->code
                : null),
            'entry_id' => $this->entry_id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'supplier_invoice_number' => $this->supplier_invoice_number,
            'supplier_invoice_series' => $this->supplier_invoice_series,
            'invoice_date' => $this->invoice_date?->format('Y-m-d'),
            'received_date' => $this->received_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'withholding_amount' => $this->withholding_amount,
            'total' => $this->total,
            'subtotal_ves' => $this->subtotal_ves,
            'tax_amount_ves' => $this->tax_amount_ves,
            'total_ves' => $this->total_ves,
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance,
            'payment_status' => $this->payment_status,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => PurchaseInvoiceLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
