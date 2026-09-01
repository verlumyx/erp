<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Catálogos que alimentan los selects de la nota de crédito a cliente.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de notas
 * de crédito nunca consulta sus tablas directamente.
 *
 * Los artículos, los clientes y las facturas de venta NO viajan aquí: son
 * padrones demasiado grandes para las props de cada pantalla. La línea busca el
 * artículo contra `items.lookup`, la cabecera el cliente contra `clients.lookup`
 * y la factura afectada contra `sales-invoices.lookup`, los tres con
 * `Select2Ajax`.
 */
class SalesCreditNoteFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly TaxRepositoryInterface $taxes,
    ) {}

    /**
     * @return array{
     *     warehouses: array<int, array{id: string, name: string}>,
     *     taxes: array<int, array<string, string>>
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouseOptions($companyId),
            'taxes' => $this->taxOptions($companyId),
        ];
    }

    /**
     * La bodega es de la línea, no de la cabecera: solo hace falta cuando la
     * mercancía reingresa al inventario.
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

    /**
     * El impuesto de la línea sale de aquí: la pantalla ya no captura el
     * porcentaje a mano. La retención viaja con él porque se practica sobre el
     * impuesto y el usuario no la elige por separado.
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
