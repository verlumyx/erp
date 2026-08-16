<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\ClientType\Commands\SearchClientTypeCommand;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;
use App\Modules\PriceList\Commands\SearchPriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

/**
 * Catálogos que alimentan los selects del formulario de cliente.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * clientes nunca consulta sus tablas directamente.
 *
 * Las rutas de entrega quedan fuera hasta que exista el módulo de Rutas
 * (Logística): la columna `route_id` ya está, pero todavía no hay qué ofrecer.
 */
class ClientFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly ClientTypeRepositoryInterface $clientTypes,
        private readonly PriceListRepositoryInterface $priceLists,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{
     *     clientTypes: array<int, array{id: string, name: string}>,
     *     priceLists: array<int, array{id: string, name: string}>,
     *     salespeople: array<int, array{id: string, name: string}>
     * }
     */
    public function execute(?string $companyId): array
    {
        $clientTypes = $this->clientTypes->search(new SearchClientTypeCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        $priceLists = $this->priceLists->search(new SearchPriceListCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        /** Cualquier usuario de la empresa puede ser el vendedor asignado. */
        $salespeople = $this->users->search(new SearchUserCommand(
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return [
            'clientTypes' => array_map(
                fn (ClientType $clientType): array => [
                    'id' => $clientType->id,
                    'name' => $clientType->name,
                ],
                $clientTypes['data'],
            ),
            'priceLists' => array_map(
                fn (PriceList $priceList): array => [
                    'id' => $priceList->id,
                    'name' => $priceList->name,
                ],
                $priceLists['data'],
            ),
            'salespeople' => array_map(
                fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                $salespeople['data'],
            ),
        ];
    }
}
