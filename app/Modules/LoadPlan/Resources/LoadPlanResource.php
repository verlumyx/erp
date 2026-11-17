<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoadPlanResource extends JsonResource
{
    public const STATUS_LABELS = [
        'draft' => 'En proceso',
        'in_transit' => 'En tránsito',
        'confirmed' => 'Confirmado',
        'cancelled' => 'Anulado',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'plan_date' => $this->plan_date?->toDateString(),
            'route_id' => $this->route_id,
            'route_code' => $this->route?->code,
            'route_name' => $this->route?->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse?->name,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_code' => $this->vehicle?->code,
            'vehicle_plate' => $this->vehicle?->plate,
            'vehicle_name' => $this->vehicle?->name,
            'vehicle_capacity_weight' => (float) ($this->vehicle?->capacity_weight ?? 0),
            'vehicle_capacity_volume' => (float) ($this->vehicle?->capacity_volume ?? 0),
            'driver_name' => $this->driver_name,
            'driver_id' => $this->driver_id,
            'driver_user_name' => $this->driver?->name,
            'helper_name' => $this->helper_name,
            'description' => $this->description,
            'total_dispatches' => (int) $this->total_dispatches,
            'total_weight' => (float) $this->total_weight,
            'total_volume' => (float) $this->total_volume,
            'status' => $this->status,
            'status_label' => self::STATUS_LABELS[$this->status] ?? $this->status,
            'is_editable' => $this->isEditable(),
            'creator_name' => $this->creator?->name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'dispatches' => $this->whenLoaded(
                'planDispatches',
                fn (): array => LoadPlanDispatchResource::collection($this->planDispatches)->resolve($request),
            ),
        ];
    }
}
