<?php

declare(strict_types=1);

namespace App\Modules\Account\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Forma la respuesta de una account. Nunca incluye `password_encrypted`:
     * las credentials solo se exponen vía el endpoint dedicado de credentials.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'service_id' => $this->service_id,
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'code' => $this->service->code,
                'max_profiles' => $this->service->max_profiles,
            ]),
            'email' => $this->email,
            'cost' => $this->cost,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'next_renewal' => $this->next_renewal?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'profiles' => $this->whenLoaded('profiles', fn () => ProfileResource::collection($this->profiles)->resolve()),
            'renewals' => $this->whenLoaded('renewals', fn () => AccountRenewalResource::collection($this->renewals)->resolve()),
            'profiles_summary' => $this->when(
                $this->relationLoaded('profiles'),
                fn () => $this->profilesSummary(),
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
