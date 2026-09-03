<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El comprador visto desde la tienda. No expone el vínculo con el cliente
 * más allá de `is_linked`: la tienda no conoce ids del ERP.
 */
class StoreCustomerApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'is_linked' => $this->isLinked(),
        ];
    }
}
