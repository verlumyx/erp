<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientAdvanceResource extends JsonResource
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
            /** Los dos indicadores del cliente, tal como están hoy. */
            'client_current_balance' => $this->whenLoaded('client', fn () => $this->client?->current_balance),
            'client_advance_balance' => $this->whenLoaded('client', fn () => $this->client?->advance_balance),
            'sales_order_id' => $this->sales_order_id,
            'sales_order_code' => $this->whenLoaded('salesOrder', fn () => $this->salesOrder?->code),
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
            'refunded_amount' => $this->refunded_amount,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            /** El cobro espejo vivo: es el que decide si el anticipo ya se recibió. */
            'collection_id' => $this->whenLoaded('collection', fn () => $this->collection?->id),
            'collection_code' => $this->whenLoaded('collection', fn () => $this->collection?->code),
            'collection_status' => $this->whenLoaded('collection', fn () => $this->collection?->status),
        ];
    }
}
