<?php

declare(strict_types=1);

namespace App\Modules\Route\Commands;

/**
 * Una parada tal como la resuelve la planificación, antes de escribirla.
 *
 * No viene de la pantalla: la arma `RoutePlanService` a partir de la plantilla
 * de clientes y de los despachos pendientes de la ruta.
 */
class RouteStopData
{
    public function __construct(
        public readonly string $clientId,
        public readonly ?string $clientAddressId,
        public readonly int $sequence,
    ) {}
}
