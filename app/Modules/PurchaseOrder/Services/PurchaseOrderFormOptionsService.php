<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\Item\Commands\SearchItemCommand;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\Supplier\Commands\SearchSupplierCommand;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Catálogos que alimentan los selects de la orden de compra.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * órdenes nunca consulta sus tablas directamente.
 */
class PurchaseOrderFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @return array{
     *     suppliers: array<int, array<string, mixed>>,
     *     warehouses: array<int, array{id: string, name: string}>,
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'suppliers' => $this->supplierOptions($companyId),
            'warehouses' => $this->warehouseOptions($companyId),
            'items' => $this->itemOptions($companyId),
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

    /**
     * Solo artículos comprables, cada uno con las unidades en que se puede
     * pedir. El select de unidad de la línea se alimenta de esta lista.
     *
     * @return array<int, array<string, mixed>>
     */
    private function itemOptions(?string $companyId): array
    {
        $result = $this->items->search(new SearchItemCommand(
            filters: ['status' => 'active', 'is_purchasable' => 'yes'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        $items = array_values(array_filter(
            $result['data'],
            fn (Item $item): bool => $item->is_purchasable === 'yes',
        ));

        (new Collection($items))->loadMissing('units.measurementUnit');

        return array_map(
            fn (Item $item): array => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'standard_cost' => $item->standard_cost,
                'units' => $item->units
                    ->where('status', 'active')
                    ->map(fn (ItemUnit $unit): array => [
                        'measurement_unit_id' => $unit->measurement_unit_id,
                        'name' => $unit->measurementUnit?->name ?? '',
                        'is_base' => $unit->is_base,
                        'conversion_factor' => $unit->conversion_factor,
                    ])
                    ->values()
                    ->all(),
            ],
            $items,
        );
    }
}
