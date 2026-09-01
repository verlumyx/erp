<?php

declare(strict_types=1);

namespace App\Modules\Route\Commands;

use App\Modules\Route\Requests\PlanRouteRequest;

/**
 * Generar las paradas de una fecha. No lleva la lista de clientes: quién se
 * visita lo decide `RoutePlanService` con la plantilla de la ruta y los
 * despachos ya asignados a ella, no la pantalla.
 */
class PlanRouteCommand
{
    public function __construct(
        public readonly string $stopDate,
    ) {}

    public static function fromRequest(PlanRouteRequest $request): self
    {
        return new self(
            stopDate: $request->string('stop_date')->toString(),
        );
    }
}
