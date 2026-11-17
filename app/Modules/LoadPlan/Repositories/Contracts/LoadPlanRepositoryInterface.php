<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Repositories\Contracts;

use App\Modules\LoadPlan\Commands\CreateLoadPlanCommand;
use App\Modules\LoadPlan\Commands\LoadPlanDispatchData;
use App\Modules\LoadPlan\Commands\SearchLoadPlanCommand;
use App\Modules\LoadPlan\Commands\UpdateLoadPlanCommand;
use App\Modules\LoadPlan\Commands\UpdateStatusLoadPlanCommand;
use App\Modules\LoadPlan\Models\LoadPlan;

interface LoadPlanRepositoryInterface
{
    public function create(CreateLoadPlanCommand $command): LoadPlan;

    public function findById(string $id, ?string $companyId = null): ?LoadPlan;

    public function findOrFail(string $id, ?string $companyId = null): LoadPlan;

    public function update(LoadPlan $model, UpdateLoadPlanCommand $command): void;

    public function updateStatus(LoadPlan $model, UpdateStatusLoadPlanCommand $command): void;

    /** @return array{ data: array<int, LoadPlan>, total: int } */
    public function search(SearchLoadPlanCommand $command): array;

    /**
     * Sincroniza los despachos asignados al plan de carga y recalcula totales (peso, volumen, cantidad).
     *
     * @param  array<int, LoadPlanDispatchData>  $dispatches
     */
    public function syncDispatches(LoadPlan $model, array $dispatches): void;

    /**
     * Consulta los despachos pendientes de entrega que pueden ser incluidos en un plan de carga,
     * trayendo su información comercial (cliente, orden de venta, factura asociada, bultos, peso y volumen).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingDispatches(string $companyId, ?string $routeId = null, ?string $excludePlanId = null): array;
}
