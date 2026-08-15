<?php

declare(strict_types=1);

namespace App\Modules\Sale\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Sale\Models\SaleRenewal
 */
class SaleRenewalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'renewed_at' => $this->renewed_at?->format('Y-m-d'),
            'previous_end_date' => $this->previous_end_date?->format('Y-m-d'),
            'new_end_date' => $this->new_end_date?->format('Y-m-d'),
            'duration_days' => $this->duration_days,
            'price' => $this->price,
            'renewed_by' => $this->renewed_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
