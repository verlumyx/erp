<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientCollectionResource extends JsonResource
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
            'origin_type' => $this->origin_type,
            'origin_id' => $this->origin_id,
            'collection_date' => $this->collection_date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'bank_account' => $this->bank_account,
            'collected_by' => $this->collected_by,
            'collected_by_name' => $this->whenLoaded('collector', fn () => $this->collector?->name),
            'route_id' => $this->route_id,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'base_currency' => $this->base_currency,
            'base_exchange_rate' => $this->base_exchange_rate,
            'amount' => $this->amount,
            'withholding_amount' => $this->withholding_amount,
            'applied_amount' => $this->applied_amount,
            'unapplied_amount' => $this->unapplied_amount,
            'amount_ves' => $this->amount_ves,
            'check_number' => $this->check_number,
            'check_date' => $this->check_date?->format('Y-m-d'),
            'check_status' => $this->check_status,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'applications' => $this->whenLoaded(
                'applications',
                fn (): array => ClientCollectionApplicationResource::collection($this->applications)->resolve($request),
            ),
        ];
    }
}
