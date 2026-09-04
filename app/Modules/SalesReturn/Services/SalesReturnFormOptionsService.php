<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Catálogos que alimentan los selects de la devolución.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * devoluciones nunca consulta sus tablas directamente.
 *
 * Viajan las bodegas —son pocas por empresa y las eligen tanto la cabecera como
 * cada línea— y quién puede firmar la recepción. Los artículos, los clientes y
 * las facturas NO viajan aquí: son padrones demasiado grandes para las props, y
 * la pantalla los busca contra `items.lookup`, `clients.lookup` y
 * `sales-invoices.lookup`.
 *
 * Los impuestos y las ubicaciones ya no hacen falta: la línea no captura ni el
 * cargo —lo copia `SalesReturnPricingService` de la factura— ni el sitio al que
 * entra la mercancía —lo pide la entrada que la devolución genera—.
 */
class SalesReturnFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{
     *     warehouses: array<int, array{id: string, name: string, type: string}>,
     *     receivers: array<int, array{id: string, name: string}>
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouseOptions($companyId),
            'receivers' => $this->receiverOptions($companyId),
        ];
    }

    /**
     * Quién puede figurar como quien recibió la mercancía: cualquier usuario de
     * la empresa.
     *
     * @return array<int, array{id: string, name: string}>
     */
    private function receiverOptions(?string $companyId): array
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
     * La cabecera dice a qué bodega reingresa la devolución y cada línea puede
     * apartarse de ella. El tipo viaja con la bodega porque la condición decide
     * a dónde puede entrar: lo dañado, solo a una de cuarentena.
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
}
