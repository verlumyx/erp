<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use App\Modules\PriceList\Commands\SearchPriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Catálogos que alimentan los selects del pedido de venta.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de pedidos
 * nunca consulta sus tablas directamente.
 *
 * Los artículos NO viajan aquí: el catálogo es demasiado grande para las props
 * de cada pantalla. La línea los busca contra `items.lookup` con `Select2Ajax`,
 * que ya trae unidades y precios de la opción elegida.
 *
 * Las rutas de entrega quedan fuera hasta que exista el módulo de Rutas
 * (Logística): la columna `route_id` ya está, pero todavía no hay qué ofrecer.
 */
class SalesOrderFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly ClientRepositoryInterface $clients,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly PriceListRepositoryInterface $priceLists,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(?string $companyId): array
    {
        return [
            'clients' => $this->clientOptions($companyId),
            'warehouses' => $this->warehouseOptions($companyId),
            'priceLists' => $this->priceListOptions($companyId),
            'salespeople' => $this->salespersonOptions($companyId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function clientOptions(?string $companyId): array
    {
        $result = $this->clients->search(new SearchClientCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        /** Una sola consulta extra para las direcciones de todos los clientes. */
        $clients = new Collection($result['data']);
        $clients->loadMissing('addresses');

        return $clients->map(fn (Client $client): array => [
            'id' => $client->id,
            'code' => $client->code,
            'name' => $client->name,
            'price_list_id' => $client->price_list_id,
            'payment_term_days' => (int) $client->payment_term_days,
            'discount_percent' => (string) $client->discount_percent,
            'credit_blocked' => $client->credit_blocked,
            'addresses' => $client->addresses
                ->where('status', 'active')
                ->map(fn (ClientAddress $address): array => [
                    'id' => $address->id,
                    'type' => $address->type,
                    'name' => $address->name,
                    'address' => $address->address,
                    'is_default' => $address->is_default,
                ])
                ->values()
                ->all(),
        ])->values()->all();
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
     * @return array<int, array{id: string, name: string}>
     */
    private function priceListOptions(?string $companyId): array
    {
        $result = $this->priceLists->search(new SearchPriceListCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (PriceList $priceList): array => [
                'id' => $priceList->id,
                'name' => $priceList->name,
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
