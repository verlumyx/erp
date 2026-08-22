<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Catálogos que alimentan los selects de la factura de venta.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * facturas nunca consulta sus tablas directamente.
 *
 * Los artículos, los clientes y los pedidos NO viajan aquí: los tres padrones
 * son demasiado grandes para las props de cada pantalla. La línea busca el
 * artículo contra `items.lookup`, la cabecera el cliente contra
 * `clients.lookup` y el documento origen contra `sales-orders.lookup`, los
 * tres con `Select2Ajax`: la opción elegida ya trae lo que la pantalla
 * necesita.
 *
 * La factura no aplica lista de precio: el precio viene del pedido origen o lo
 * captura el usuario, y queda congelado en la línea.
 */
class SalesInvoiceFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly UserRepositoryInterface $users,
        private readonly TaxRepositoryInterface $taxes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouseOptions($companyId),
            'salespeople' => $this->salespersonOptions($companyId),
            'taxes' => $this->taxOptions($companyId),
        ];
    }

    /**
     * @return array<int, array{id: string, code: string|null, name: string, is_default: string}>
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
                'code' => $warehouse->code,
                'name' => $warehouse->name,
                'is_default' => $warehouse->is_default,
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

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function salespersonOptions(?string $companyId): array
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
