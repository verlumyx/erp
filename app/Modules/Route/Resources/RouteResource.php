<?php

declare(strict_types=1);

namespace App\Modules\Route\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
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
            'description' => $this->description,
            'type' => $this->type,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'driver_id' => $this->driver_id,
            'driver_name' => $this->whenLoaded('driver', fn () => $this->driver?->name),
            'salesperson_id' => $this->salesperson_id,
            'salesperson_name' => $this->whenLoaded('salesperson', fn () => $this->salesperson?->name),
            'vehicle_plate' => $this->vehicle_plate,
            'vehicle_capacity_weight' => $this->vehicle_capacity_weight,
            'vehicle_capacity_volume' => $this->vehicle_capacity_volume,
            'frequency' => $this->frequency,
            'weekdays' => $this->weekdays ?? [],
            'zone' => $this->zone,
            'city' => $this->city,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'estimated_distance_km' => $this->estimated_distance_km,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            /** Cuántos clientes fijos tiene, para el listado. */
            'clients_count' => $this->whenNotNull($this->clients_count),
            'clients' => $this->whenLoaded(
                'clients',
                fn (): array => RouteClientResource::collection($this->clients)->resolve($request),
            ),
        ];
    }
}
