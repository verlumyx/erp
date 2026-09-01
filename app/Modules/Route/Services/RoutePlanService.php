<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Route\Commands\PlanRouteCommand;
use App\Modules\Route\Commands\RouteStopData;
use App\Modules\Route\Exceptions\RouteNotFoundException;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Models\RouteClient;
use App\Modules\Route\Models\RouteStop;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Genera las paradas de una fecha.
 *
 * Quién se visita sale de dos sitios, y en este orden: primero la plantilla de
 * la ruta —los clientes fijos, con su orden habitual— y después los despachos
 * ya asignados a la ruta que todavía no se entregaron. Un cliente que está en
 * los dos aparece una sola vez, en el lugar que le da la plantilla; uno que
 * solo tiene despacho se agrega al final, porque no es parte del recorrido
 * habitual.
 *
 * Es idempotente: replanificar el mismo día reescribe lo que aún no se visitó y
 * deja intacto lo que el conductor ya registró.
 */
class RoutePlanService
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository,
        private readonly RoutePendingWorkService $pending,
        private readonly DispatchRepositoryInterface $dispatches,
    ) {}

    /**
     * @return array<int, RouteStop>
     */
    public function execute(string $id, PlanRouteCommand $command, ?string $companyId = null): array
    {
        $route = $this->repository->findById($id, $companyId);

        if ($route === null) {
            throw new RouteNotFoundException;
        }

        return DB::transaction(function () use ($route, $command): array {
            $dispatches = $this->pending->pendingDispatches($route, $command->stopDate);

            $stops = $this->repository->syncStops(
                $route,
                $command->stopDate,
                $this->resolveStops($route, $dispatches),
            );

            $this->linkDispatches($dispatches, $stops);

            return $stops;
        });
    }

    /**
     * @param  array<int, Dispatch>  $dispatches
     * @return array<int, RouteStopData>
     */
    private function resolveStops(Route $route, array $dispatches): array
    {
        $stops = [];
        $sequence = 0;

        foreach ($this->repository->activeClients($route) as $client) {
            /** @var RouteClient $client */
            $stops[$client->client_id] = new RouteStopData(
                clientId: $client->client_id,
                clientAddressId: $client->client_address_id,
                sequence: ++$sequence,
            );
        }

        foreach ($dispatches as $dispatch) {
            if (isset($stops[$dispatch->client_id])) {
                continue;
            }

            $stops[$dispatch->client_id] = new RouteStopData(
                clientId: $dispatch->client_id,
                clientAddressId: $dispatch->client_address_id,
                sequence: ++$sequence,
            );
        }

        return array_values($stops);
    }

    /**
     * Ata cada despacho a la parada en la que se entrega. La columna
     * `route_stop_id` existe justo para esto, y este es el único momento en el
     * que se sabe: hasta que el día no se planifica, la parada no existe.
     *
     * @param  array<int, Dispatch>  $dispatches
     * @param  array<int, RouteStop>  $stops
     */
    private function linkDispatches(array $dispatches, array $stops): void
    {
        $stopOf = [];

        foreach ($stops as $stop) {
            $stopOf[$stop->client_id] = $stop->id;
        }

        foreach ($dispatches as $dispatch) {
            $stopId = $stopOf[$dispatch->client_id] ?? null;

            if ($stopId === null || $dispatch->route_stop_id === $stopId) {
                continue;
            }

            $this->dispatches->assignRouteStop($dispatch, $stopId);
        }
    }
}
