<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Resources;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
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
            'plate' => $this->plate,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'vehicle_type' => $this->vehicle_type,
            'vehicle_type_label' => Vehicle::VEHICLE_TYPES[$this->vehicle_type] ?? $this->vehicle_type,
            'capacity_weight' => (float) $this->capacity_weight,
            'capacity_volume' => (float) $this->capacity_volume,
            'notes' => $this->notes,
            'status' => $this->status,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
