<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;

/**
 * Catálogos que alimentan los selects del traslado.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * traslados nunca consulta sus tablas directamente.
 *
 * Los artículos, los lotes y las series NO viajan aquí: son padrones demasiado
 * grandes para las props de cada pantalla. La línea busca el artículo contra
 * `items.lookup`, el lote contra `item-lots.lookup` y la serie contra
 * `item-serials.lookup`.
 *
 * No hay impuestos: el traslado no grava nada. Entre bodegas propias no hay
 * venta ni compra, solo mercancía que cambia de sitio.
 */
class TransferFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{
     *     warehouses: array<int, array{id: string, name: string, type: string}>,
     *     locations: array<int, array{id: string, warehouse_id: string, name: string, is_default: string}>,
     *     drivers: array<int, array{id: string, name: string}>
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouseOptions($companyId),
            'locations' => $this->locationOptions($companyId),
            'drivers' => $this->driverOptions($companyId),
        ];
    }

    /**
     * Quién puede figurar como conductor: cualquier usuario de la empresa.
     *
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

    /**
     * Las tres bodegas del traslado —origen, destino y tránsito— salen de la
     * misma lista: la pantalla es la que impide repetirlas.
     *
     * @return array<int, array{id: string, name: string, type: string}>
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
                'type' => $warehouse->type,
            ],
            $result['data'],
        );
    }

    /**
     * Las ubicaciones viajan enteras y con su bodega: la pantalla filtra por la
     * bodega elegida sin volver al servidor. Son pocas por empresa, a diferencia
     * de artículos o lotes.
     *
     * @return array<int, array{id: string, warehouse_id: string, name: string, is_default: string}>
     */
    private function locationOptions(?string $companyId): array
    {
        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (WarehouseLocation $location): array => [
                'id' => $location->id,
                'warehouse_id' => $location->warehouse_id,
                'name' => $location->name,
                'is_default' => $location->is_default,
            ],
            $result['data'],
        );
    }
}
