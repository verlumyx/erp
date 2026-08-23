<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierPaymentResource extends JsonResource
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
            'origin_type' => $this->origin_type,
            'origin_id' => $this->origin_id,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'bank_account' => $this->bank_account,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'amount' => $this->amount,
            'withholding_amount' => $this->withholding_amount,
            'applied_amount' => $this->applied_amount,
            'unapplied_amount' => $this->unapplied_amount,
            'amount_ves' => $this->amount_ves,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'applications' => $this->whenLoaded(
                'applications',
                fn (): array => SupplierPaymentApplicationResource::collection($this->applications)->resolve($request),
            ),
        ];
    }
}
