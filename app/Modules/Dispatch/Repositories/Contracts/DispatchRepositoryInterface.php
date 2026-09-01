<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Repositories\Contracts;

use App\Modules\Dispatch\Commands\CreateDispatchCommand;
use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Commands\UpdateDispatchCommand;
use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Commands\WriteDispatchDeliveryCommand;
use App\Modules\Dispatch\Commands\WriteDispatchLineCostCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Models\DispatchLine;

interface DispatchRepositoryInterface
{
    /**
     * @param  array<int, float>  $unitCosts  Costo de salida por línea, en el
     *                                        mismo orden en que llegan. Lo
     *                                        resuelve `DispatchCostService`.
     */
    public function create(CreateDispatchCommand $command, array $unitCosts): void;

    public function findById(string $id, ?string $companyId = null): ?Dispatch;

    public function findOrFail(string $id, ?string $companyId = null): Dispatch;

    /**
     * @param  array<int, float>  $unitCosts
     */
    public function update(Dispatch $model, UpdateDispatchCommand $command, array $unitCosts): void;

    public function updateStatus(Dispatch $model, UpdateStatusDispatchCommand $command): void;

    /**
     * Escribe el resultado del viaje ya resuelto. Solo lo llama
     * `DispatchDeliveryService`.
     */
    public function writeDelivery(Dispatch $model, WriteDispatchDeliveryCommand $command): Dispatch;

    /**
     * Escribe en la línea el costo con el que salió de verdad. Solo lo llama
     * `DispatchPostingService`, con lo que el kardex acaba de valorar.
     */
    public function writeLineCost(DispatchLine $line, WriteDispatchLineCostCommand $command): DispatchLine;

    /**
     * Ata el despacho a la parada de la ruta en la que se entrega. Solo lo
     * llama `RoutePlanService`: hasta que el día no se planifica, la parada no
     * existe.
     */
    public function assignRouteStop(Dispatch $model, ?string $routeStopId): Dispatch;

    /** Recalcula los totales de la cabecera a partir de sus líneas activas. */
    public function refreshTotals(Dispatch $model): Dispatch;

    /** @return array{ data: Dispatch[], total: int } */
    public function search(SearchDispatchCommand $command): array;

    /**
     * Líneas activas del despacho, con lo que el kardex necesita para valorar
     * la salida.
     *
     * @return array<int, DispatchLine>
     */
    public function activeLines(Dispatch $model): array;

    /**
     * Cantidad ya despachada de cada línea origen por despachos distintos del
     * indicado. Es lo que limita cuánto puede despachar una línea nueva.
     *
     * @param  array<int, string>  $sourceLineIds
     * @return array<string, float> Id de la línea origen → cantidad despachada.
     */
    public function dispatchedQuantities(array $sourceLineIds, ?string $exceptDispatchId = null): array;
}
