<?php

declare(strict_types=1);

namespace App\Modules\Route\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ruta vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `RouteResource`: solo `value`, `label` y lo
 * que el despacho o el traslado copian al elegirla —la bodega de salida, el
 * conductor y el vehículo— sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `RouteOptionSearchService`.
 */
class RouteOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => $this->code ? "{$this->code} — {$this->name}" : $this->name,
            'meta' => [
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
                'zone' => $this->zone,
                'city' => $this->city,
                'warehouse_id' => $this->warehouse_id,
                'warehouse_name' => $this->warehouse?->name,
                'driver_id' => $this->driver_id,
                'driver_name' => $this->driver?->name,
                'salesperson_id' => $this->salesperson_id,
                'vehicle_plate' => $this->vehicle_plate,
                'vehicle_capacity_weight' => (string) $this->vehicle_capacity_weight,
                'vehicle_capacity_volume' => (string) $this->vehicle_capacity_volume,
                'frequency' => $this->frequency,
                'weekdays' => $this->weekdays ?? [],
                'status' => $this->status,
            ],
        ];
    }
}
