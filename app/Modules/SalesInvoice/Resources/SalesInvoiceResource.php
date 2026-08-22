<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceResource extends JsonResource
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
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            /** El código del pedido de origen: es lo que el usuario reconoce. */
            'sourceable_code' => $this->whenLoaded('sourceable', fn () => $this->sourceable?->code),
            'dispatch_id' => $this->dispatch_id,
            'client_address_id' => $this->client_address_id,
            'client_address_name' => $this->whenLoaded('clientAddress', fn () => $this->clientAddress?->name),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'salesperson_id' => $this->salesperson_id,
            'salesperson_name' => $this->whenLoaded('salesperson', fn () => $this->salesperson?->name),
            'invoice_series' => $this->invoice_series,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'sale_type' => $this->sale_type,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'affects_inventory' => $this->affects_inventory,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'withholding_amount' => $this->withholding_amount,
            'freight_amount' => $this->freight_amount,
            'total' => $this->total,
            'total_cost' => $this->total_cost,
            'subtotal_ves' => $this->subtotal_ves,
            'tax_amount_ves' => $this->tax_amount_ves,
            'total_ves' => $this->total_ves,
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance,
            'payment_status' => $this->payment_status,
            'fiscal_status' => $this->fiscal_status,
            'fiscal_uuid' => $this->fiscal_uuid,
            'printed_at' => $this->printed_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'lines' => $this->whenLoaded(
                'lines',
                fn (): array => SalesInvoiceLineResource::collection($this->lines)->resolve($request),
            ),
        ];
    }
}
