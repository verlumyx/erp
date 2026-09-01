<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
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
 * Catálogos que alimentan los selects del despacho.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * despachos nunca consulta sus tablas directamente.
 *
 * Los artículos, los clientes, los pedidos, los lotes y las series NO viajan
 * aquí: son padrones demasiado grandes para las props de cada pantalla. La
 * línea busca el artículo contra `items.lookup`, el lote contra
 * `item-lots.lookup` y la serie contra `item-serials.lookup`; la cabecera busca
 * el cliente contra `clients.lookup` y el pedido de origen contra
 * `sales-orders.lookup`.
 */
class DispatchFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly TaxRepositoryInterface $taxes,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{
     *     warehouses: array<int, array{id: string, name: string, type: string}>,
     *     locations: array<int, array{id: string, warehouse_id: string, name: string, is_default: string}>,
     *     taxes: array<int, array<string, string>>,
     *     drivers: array<int, array{id: string, name: string}>
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouseOptions($companyId),
            'locations' => $this->locationOptions($companyId),
            'taxes' => $this->taxOptions($companyId),
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
     * La bodega es de la cabecera: todo el despacho sale del mismo sitio.
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

    /**
     * El impuesto de la línea sale de aquí. En el despacho es informativo —no
     * factura—, pero la guía enseña lo mismo que el pedido.
     *
     * @return array<int, array{id: string, code: string, name: string, percentage: string, has_withholding: string, withholding_percentage: string}>
     */
    private function taxOptions(?string $companyId): array
    {
        $result = $this->taxes->search(new SearchTaxCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (Tax $tax): array => [
                'id' => $tax->id,
                'code' => $tax->code,
                'name' => $tax->name,
                'percentage' => (string) $tax->percentage,
                'has_withholding' => $tax->has_withholding,
                'withholding_percentage' => (string) $tax->withholding_percentage,
            ],
            $result['data'],
        );
    }
}
