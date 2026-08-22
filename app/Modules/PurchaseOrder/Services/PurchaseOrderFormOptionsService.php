<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\Supplier\Commands\SearchSupplierCommand;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Catálogos que alimentan los selects de la orden de compra.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * órdenes nunca consulta sus tablas directamente.
 *
 * Los artículos NO viajan aquí: el catálogo es demasiado grande para las props
 * de cada pantalla. La línea los busca contra `items.lookup` con `Select2Ajax`,
 * que ya trae unidades y costo de la opción elegida.
 */
class PurchaseOrderFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly WarehouseRepositoryInterface $warehouses,
    ) {}

    /**
     * @return array{
     *     suppliers: array<int, array<string, mixed>>,
     *     warehouses: array<int, array{id: string, name: string}>
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'suppliers' => $this->supplierOptions($companyId),
            'warehouses' => $this->warehouseOptions($companyId),
        ];
    }

    /**
     * El proveedor arrastra su moneda y sus días de crédito: la pantalla los
     * copia en la cabecera al seleccionarlo, y ahí quedan editables.
     *
     * @return array<int, array<string, mixed>>
     */
    private function supplierOptions(?string $companyId): array
    {
        $result = $this->suppliers->search(new SearchSupplierCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'name' => $supplier->name,
                'currency' => $supplier->currency,
                'payment_term_days' => $supplier->payment_term_days,
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
}
