<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Services;

use App\Modules\Route\Commands\SearchRouteCommand;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Vehicle\Commands\SearchVehicleCommand;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Repositories\Contracts\VehicleRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

class LoadPlanFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly RouteRepositoryInterface $routes,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{
     *     routes: array<int, array{id: string, code: ?string, name: string, warehouse_id: ?string, driver_id: ?string, driver_name: ?string, vehicle_plate: ?string}>,
     *     warehouses: array<int, array{id: string, name: string}>,
     *     vehicles: array<int, array{id: string, code: ?string, plate: string, name: string, capacity_weight: float, capacity_volume: float}>,
     *     drivers: array<int, array{id: string, name: string}>,
     *     statuses: array<string, string>
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'routes' => $this->routeOptions($companyId),
            'warehouses' => $this->warehouseOptions($companyId),
            'vehicles' => $this->vehicleOptions($companyId),
            'drivers' => $this->driverOptions($companyId),
            'statuses' => [
                'draft' => 'En proceso',
                'in_transit' => 'En tránsito',
                'confirmed' => 'Confirmado',
                'cancelled' => 'Anulado',
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, code: ?string, name: string, warehouse_id: ?string, driver_id: ?string, driver_name: ?string, vehicle_plate: ?string}>
     */
    private function routeOptions(?string $companyId): array
    {
        $result = $this->routes->search(new SearchRouteCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (Route $route): array => [
                'id' => $route->id,
                'code' => $route->code,
                'name' => $route->name,
                'warehouse_id' => $route->warehouse_id,
                'driver_id' => $route->driver_id,
                'driver_name' => $route->driver?->name,
                'vehicle_plate' => $route->vehicle_plate,
            ],
            $result['data'],
        );
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function warehouseOptions(?string $companyId): array
    {
        $result = $this->warehouses->search(new SearchWarehouseCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (Warehouse $warehouse): array => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ],
            $result['data'],
        );
    }

    /**
     * @return array<int, array{id: string, code: ?string, plate: string, name: string, capacity_weight: float, capacity_volume: float}>
     */
    private function vehicleOptions(?string $companyId): array
    {
        $result = $this->vehicles->search(new SearchVehicleCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'code' => $vehicle->code,
                'plate' => $vehicle->plate,
                'name' => $vehicle->name,
                'capacity_weight' => (float) $vehicle->capacity_weight,
                'capacity_volume' => (float) $vehicle->capacity_volume,
            ],
            $result['data'],
        );
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function driverOptions(?string $companyId): array
    {
        $result = $this->users->search(new SearchUserCommand(
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            $result['data'],
        );
    }
}
