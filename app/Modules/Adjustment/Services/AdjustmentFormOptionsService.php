<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;
use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;

/**
 * Catálogos que alimentan los selects del ajuste.
 *
 * Se resuelven a través de los repositorios de sus módulos: el módulo de
 * ajustes nunca consulta sus tablas directamente.
 *
 * Los artículos, los lotes y las series NO viajan aquí: son padrones demasiado
 * grandes para las props de cada pantalla. La línea busca el artículo contra
 * `items.lookup`, el lote contra `item-lots.lookup` y la serie contra
 * `item-serials.lookup`.
 */
class AdjustmentFormOptionsService
{
    private const MAX_OPTIONS = 500;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly UserRepositoryInterface $users,
        private readonly ConfigurationRepositoryInterface $configurations,
    ) {}

    /**
     * @return array{
     *     warehouses: array<int, array{id: string, name: string, type: string}>,
     *     locations: array<int, array{id: string, warehouse_id: string, name: string, is_default: string}>,
     *     counters: array<int, array{id: string, name: string}>,
     *     approval_threshold: string
     * }
     */
    public function execute(?string $companyId): array
    {
        return [
            'warehouses' => $this->warehouseOptions($companyId),
            'locations' => $this->locationOptions($companyId),
            'counters' => $this->counterOptions($companyId),
            'approval_threshold' => $this->approvalThreshold($companyId),
        ];
    }

    /**
     * Solo las bodegas. El listado las necesita para su filtro y no tiene por
     * qué cargar el resto de los catálogos del formulario.
     *
     * @return array<int, array{id: string, name: string, type: string}>
     */
    public function warehouses(?string $companyId): array
    {
        return $this->warehouseOptions($companyId);
    }

    /**
     * A partir de cuánto impacto el ajuste necesita la firma de alguien
     * distinto de quien lo registró. Viaja a la pantalla para que el aviso
     * salga mientras se captura, no al intentar confirmar.
     */
    private function approvalThreshold(?string $companyId): string
    {
        if ($companyId === null) {
            return '0.00';
        }

        $configuration = $this->configurations->findByCompany($companyId);

        return (string) ($configuration?->adjustment_approval_threshold ?? '0.00');
    }

    /**
     * Quién puede figurar como quien contó una línea: cualquier usuario de la
     * empresa.
     *
     * @return array<int, array{id: string, name: string}>
     */
    private function counterOptions(?string $companyId): array
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
     * La bodega es de la cabecera: un ajuste corrige una sola bodega. El tipo
     * viaja con ella porque la pantalla lo muestra al elegirla.
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
     * Las ubicaciones viajan enteras y con su bodega: la pantalla filtra por la
     * bodega elegida sin volver al servidor. Son pocas por empresa, a
     * diferencia de artículos o lotes.
     *
     * @return array<int, array{id: string, warehouse_id: string, name: string, is_default: string}>
     */
    private function locationOptions(?string $companyId): array
    {
        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: ['status' => 'active'],
            limit: self::MAX_OPTIONS,
            companyId: $companyId,
        ));

        return array_map(
            fn (WarehouseLocation $location): array => [
                'id' => $location->id,
                'warehouse_id' => $location->warehouse_id,
                'name' => $location->name,
                'is_default' => $location->is_default,
            ],
            $result['data'],
        );
    }
}
