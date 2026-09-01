<?php

declare(strict_types=1);

namespace App\Modules\Route\Commands;

use App\Modules\Route\Requests\UpdateRouteRequest;

class UpdateRouteCommand
{
    /**
     * @param  array<int, RouteClientData>  $clients
     * @param  array<int, string>|null  $weekdays
     */
    public function __construct(
        public readonly string $name,
        public readonly array $clients = [],
        public readonly string $type = 'delivery',
        public readonly string $frequency = 'weekly',
        public readonly ?array $weekdays = null,
        public readonly ?string $description = null,
        public readonly ?string $warehouseId = null,
        public readonly ?string $driverId = null,
        public readonly ?string $salespersonId = null,
        public readonly ?string $vehiclePlate = null,
        public readonly float $vehicleCapacityWeight = 0.0,
        public readonly float $vehicleCapacityVolume = 0.0,
        public readonly ?string $zone = null,
        public readonly ?string $city = null,
        public readonly int $estimatedDurationMinutes = 0,
        public readonly float $estimatedDistanceKm = 0.0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateRouteRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            clients: RouteClientData::collection($request->input('clients', [])),
            type: (string) $request->input('type', 'delivery'),
            frequency: (string) $request->input('frequency', 'weekly'),
            weekdays: $request->input('weekdays'),
            description: $request->input('description'),
            warehouseId: $request->input('warehouse_id'),
            driverId: $request->input('driver_id'),
            salespersonId: $request->input('salesperson_id'),
            vehiclePlate: $request->input('vehicle_plate'),
            vehicleCapacityWeight: (float) $request->input('vehicle_capacity_weight', 0),
            vehicleCapacityVolume: (float) $request->input('vehicle_capacity_volume', 0),
            zone: $request->input('zone'),
            city: $request->input('city'),
            estimatedDurationMinutes: (int) $request->input('estimated_duration_minutes', 0),
            estimatedDistanceKm: (float) $request->input('estimated_distance_km', 0),
            notes: $request->input('notes'),
        );
    }
}
