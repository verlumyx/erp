<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Catálogos que alimentan los selects de la devolución.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * devoluciones nunca consulta sus tablas directamente.
 *
 * Solo viajan las bodegas: son pocas por empresa y las eligen tanto la cabecera
 * como cada línea. Los artículos, los proveedores y las facturas NO viajan
 * aquí —son padrones demasiado grandes para las props— y la pantalla los busca
 * contra `items.lookup`, `suppliers.lookup` y `purchase-invoices.lookup`.
 *
 * Los impuestos y las ubicaciones ya no hacen falta: la línea no captura ni el
 * cargo —lo copia `PurchaseReturnPricingService` de la factura— ni el sitio del
 * que sale la mercancía —lo pide el despacho que la devolución genera—.
 */
class PurchaseReturnFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
    ) {}

    /**
     * @return array{ warehouses: array<int, array{id: string, name: string}> }
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouseOptions($companyId),
        ];
    }

    /**
     * La cabecera dice de qué bodega sale la devolución y cada línea puede
     * apartarse de ella.
     *
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
}
