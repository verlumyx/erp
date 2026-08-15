<?php

declare(strict_types=1);

namespace App\Modules\Plan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'service_id' => $this->service_id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'duration_days' => $this->duration_days,
            'sale_price' => $this->sale_price,
            'roi_target_pct' => $this->roi_target_pct,
            'active' => $this->active,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
