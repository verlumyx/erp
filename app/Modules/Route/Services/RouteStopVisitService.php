<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Route\Commands\RegisterRouteStopVisitCommand;
use App\Modules\Route\Exceptions\RouteNotFoundException;
use App\Modules\Route\Exceptions\RouteStopNotFoundException;
use App\Modules\Route\Models\RouteStop;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;

/**
 * Registra lo que pasó en una parada.
 *
 * No mueve inventario ni cierra nada más: la entrega de la mercancía la
 * registra el despacho, y esta es la bitácora del recorrido —a qué hora se
 * llegó, si se pudo visitar y, si no, por qué—.
 */
class RouteStopVisitService
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository,
    ) {}

    public function execute(
        string $routeId,
        string $stopId,
        RegisterRouteStopVisitCommand $command,
        ?string $companyId = null,
    ): RouteStop {
        $route = $this->repository->findById($routeId, $companyId);

        if ($route === null) {
            throw new RouteNotFoundException;
        }

        $stop = $this->repository->findStop($route, $stopId);

        if ($stop === null) {
            throw new RouteStopNotFoundException;
        }

        return $this->repository->writeStopVisit($stop, $command);
    }
}
