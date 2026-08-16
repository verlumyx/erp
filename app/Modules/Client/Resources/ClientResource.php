<?php

declare(strict_types=1);

namespace App\Modules\Client\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'client_type_id' => $this->client_type_id,
            'client_type_name' => $this->whenLoaded('clientType', fn () => $this->clientType?->name),
            'price_list_id' => $this->price_list_id,
            'price_list_name' => $this->whenLoaded('priceList', fn () => $this->priceList?->name),
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'payment_term_days' => $this->payment_term_days,
            'credit_limit' => $this->credit_limit,
            'credit_blocked' => $this->credit_blocked,
            'current_balance' => $this->current_balance,
            'advance_balance' => $this->advance_balance,
            'discount_percent' => $this->discount_percent,
            'salesperson_id' => $this->salesperson_id,
            'salesperson_name' => $this->whenLoaded('salesperson', fn () => $this->salesperson?->name),
            'route_id' => $this->route_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'contacts' => $this->whenLoaded(
                'contacts',
                fn (): array => ClientContactResource::collection($this->contacts)->resolve($request),
            ),
            'addresses' => $this->whenLoaded(
                'addresses',
                fn (): array => ClientAddressResource::collection($this->addresses)->resolve($request),
            ),
        ];
    }
}
