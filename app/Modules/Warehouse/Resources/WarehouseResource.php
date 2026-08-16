<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'phone' => $this->phone,
            'city' => $this->city,
            'responsible_user_id' => $this->responsible_user_id,
            'responsible_user_name' => $this->whenLoaded('responsible', fn () => $this->responsible?->name),
            'is_default' => $this->is_default,
            'allows_negative_stock' => $this->allows_negative_stock,
            'uses_locations' => $this->uses_locations,
            'is_sales_available' => $this->is_sales_available,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
