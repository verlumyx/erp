<?php

declare(strict_types=1);

namespace App\Modules\Route\Repositories\Contracts;

use App\Modules\Route\Commands\CreateRouteCommand;
use App\Modules\Route\Commands\RegisterRouteStopVisitCommand;
use App\Modules\Route\Commands\RouteStopData;
use App\Modules\Route\Commands\SearchRouteCommand;
use App\Modules\Route\Commands\UpdateRouteCommand;
use App\Modules\Route\Commands\UpdateStatusRouteCommand;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Models\RouteStop;

interface RouteRepositoryInterface
{
    public function create(CreateRouteCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Route;

    public function findOrFail(string $id, ?string $companyId = null): Route;

    public function update(Route $model, UpdateRouteCommand $command): void;

    public function updateStatus(Route $model, UpdateStatusRouteCommand $command): void;

    /** @return array{ data: Route[], total: int } */
    public function search(SearchRouteCommand $command): array;

    /**
     * Clientes fijos de la ruta, en el orden de visita.
     *
     * @return array<int, \App\Modules\Route\Models\RouteClient>
     */
    public function activeClients(Route $model): array;

    /**
     * Paradas de una fecha, en el orden de visita.
     *
     * @return array<int, RouteStop>
     */
    public function stopsOn(Route $model, string $stopDate): array;

    public function findStop(Route $model, string $stopId): ?RouteStop;

    /**
     * Escribe las paradas de una fecha ya resueltas por `RoutePlanService`.
     *
     * Es idempotente: replanificar el mismo día reescribe las paradas que aún
     * no se visitaron, respeta las ya cerradas y desactiva las que dejaron de
     * corresponder.
     *
     * @param  array<int, RouteStopData>  $stops
     * @return array<int, RouteStop>
     */
    public function syncStops(Route $model, string $stopDate, array $stops): array;

    public function writeStopVisit(RouteStop $stop, RegisterRouteStopVisitCommand $command): RouteStop;

    /**
     * Paradas abiertas —pendientes o con el vehículo ya en el sitio— de toda la
     * ruta. Son las que impiden desactivarla.
     */
    public function openStopsCount(Route $model): int;
}
