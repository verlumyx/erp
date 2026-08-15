<?php

declare(strict_types=1);

namespace App\Modules\Sale\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Sale\Models\Sale
 */
class SaleResource extends JsonResource
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
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'code' => $this->client->code,
            ]),
            'plan_id' => $this->plan_id,
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'code' => $this->plan->code,
                'duration_days' => $this->plan->duration_days,
                'sale_price' => $this->plan->sale_price,
            ]),
            'service_id' => $this->service_id,
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'code' => $this->service->code,
            ]),
            'agent_id' => $this->agent_id,
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent->id,
                'name' => $this->agent->name,
            ]),
            'capacity' => $this->capacity,
            'duration_days' => $this->duration_days,
            'price' => $this->price,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'is_in_grace_period' => $this->isInGracePeriod(),
            'can_be_renewed' => $this->canBeRenewed(),
            'can_be_reactivated' => $this->canBeReactivated(),
            'days_until_expiration' => $this->daysUntilExpiration(),
            'sale_profiles' => $this->whenLoaded('saleProfiles', fn () => SaleProfileResource::collection($this->saleProfiles)->resolve()),
            'renewals' => $this->whenLoaded('renewals', fn () => SaleRenewalResource::collection($this->renewals)->resolve()),
            'transactions' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'amount' => $transaction->amount,
                'date' => $transaction->date?->format('Y-m-d'),
                'description' => $transaction->description,
            ])->all()),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
