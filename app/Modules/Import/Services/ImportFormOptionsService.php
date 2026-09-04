<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Catálogos que alimentan los selects del expediente.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * importaciones nunca consulta sus tablas directamente.
 *
 * Ni los proveedores, ni las facturas, ni las recepciones viajan aquí: son
 * padrones demasiado grandes para las props de cada pantalla. El costo busca su
 * proveedor contra `suppliers.lookup` y su factura contra
 * `purchase-invoices.lookup`; la recepción se busca contra el endpoint propio
 * del módulo, que además esconde las entradas ya comprometidas.
 */
class ImportFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
    ) {}

    /**
     * @return array{warehouses: array<int, array{id: string, name: string, type: string}>}
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouses($companyId),
        ];
    }

    /**
     * La bodega es de la cabecera: un expediente revaloriza una sola bodega,
     * porque el ajuste que genera lleva una sola en su cabecera.
     *
     * @return array<int, array{id: string, name: string, type: string}>
     */
    public function warehouses(?string $companyId): array
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
}
