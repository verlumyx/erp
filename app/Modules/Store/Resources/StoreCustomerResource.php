<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreCustomerResource extends JsonResource
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
            'client' => $this->whenLoaded('client', fn (): ?array => $this->client === null ? null : [
                'id' => $this->client->id,
                'code' => $this->client->code,
                'name' => $this->client->name,
                'status' => $this->client->status,
            ]),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'status' => $this->status,
            'link_source' => $this->link_source,
            'linked_at' => $this->linked_at?->format('Y-m-d H:i:s'),
            'linked_by' => $this->linked_by,
            'linker' => $this->whenLoaded('linker', fn (): ?array => $this->linker === null ? null : [
                'id' => $this->linker->id,
                'name' => $this->linker->name,
            ]),
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'invitation_expires_at' => $this->invitation_expires_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
