<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierAdvanceResource extends JsonResource
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
            /** Los dos indicadores del proveedor, tal como están hoy. */
            'supplier_current_balance' => $this->whenLoaded('supplier', fn () => $this->supplier?->current_balance),
            'supplier_advance_balance' => $this->whenLoaded('supplier', fn () => $this->supplier?->advance_balance),
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order_code' => $this->whenLoaded('purchaseOrder', fn () => $this->purchaseOrder?->code),
            'advance_date' => $this->advance_date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'bank_account' => $this->bank_account,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'amount' => $this->amount,
            'applied_amount' => $this->applied_amount,
            'balance' => $this->balance,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            /** El pago espejo vivo: es el que decide si el anticipo ya se entregó. */
            'payment_id' => $this->whenLoaded('payment', fn () => $this->payment?->id),
            'payment_code' => $this->whenLoaded('payment', fn () => $this->payment?->code),
            'payment_status' => $this->whenLoaded('payment', fn () => $this->payment?->status),
        ];
    }
}
