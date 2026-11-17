<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Resources;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vehículo visto como opción de un select remoto (`Select2Ajax`).
 */
class VehicleOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        $label = $this->code
            ? "{$this->plate} — {$this->name} ({$this->code})"
            : "{$this->plate} — {$this->name}";

        return [
            'value' => $this->id,
            'label' => $label,
            'meta' => [
                'id' => $this->id,
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
                'status' => $this->status,
            ],
        ];
    }
}
