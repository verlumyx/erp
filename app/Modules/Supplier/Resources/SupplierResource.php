<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'supplier_type_id' => $this->supplier_type_id,
            'supplier_type_name' => $this->whenLoaded('supplierType', fn () => $this->supplierType?->name),
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'currency' => $this->currency,
            'payment_term_days' => $this->payment_term_days,
            'credit_limit' => $this->credit_limit,
            'current_balance' => $this->current_balance,
            'advance_balance' => $this->advance_balance,
            'lead_time_days' => $this->lead_time_days,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'contacts' => $this->whenLoaded(
                'contacts',
                fn (): array => SupplierContactResource::collection($this->contacts)->resolve($request),
            ),
            'addresses' => $this->whenLoaded(
                'addresses',
                fn (): array => SupplierAddressResource::collection($this->addresses)->resolve($request),
            ),
        ];
    }
}
