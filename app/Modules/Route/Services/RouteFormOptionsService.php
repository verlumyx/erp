<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Catálogos que alimentan los selects de la ruta.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de rutas
 * nunca consulta sus tablas directamente.
 *
 * Los clientes NO viajan aquí: son un padrón que crece con el negocio y sería
 * el más grande de esta pantalla, precisamente la que puede tener cincuenta.
 * La fila los busca contra `clients.lookup`, y de la opción elegida salen sus
 * direcciones, así que tampoco hay un catálogo de direcciones.
 */
class RouteFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{
     *     warehouses: array<int, array{id: string, name: string, type: string}>,
     *     users: array<int, array{id: string, name: string}>
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouseOptions($companyId),
            'users' => $this->userOptions($companyId),
        ];
    }

    /**
     * De dónde sale la carga del día.
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
     * Una sola lista para el conductor y para el vendedor: quién hace cada
     * papel lo decide la ruta, no una clasificación del usuario.
     *
     * @return array<int, array{id: string, name: string}>
     */
    private function userOptions(?string $companyId): array
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
