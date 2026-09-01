<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;

/**
 * Lo que la ruta todavía debe: paradas sin cerrar y despachos asignados que aún
 * no se entregaron.
 *
 * Vive aparte porque lo preguntan dos sitios muy distintos —desactivar la ruta
 * y planificar el día— y en ambos la respuesta es la misma consulta. Los
 * despachos se piden a su repositorio: el módulo de rutas nunca consulta
 * `app_dispatches` directamente.
 */
class RoutePendingWorkService
{
    /** Tope de despachos que se examinan de una ruta en una misma pregunta. */
    private const MAX_DISPATCHES = 500;

    public function __construct(
        private readonly RouteRepositoryInterface $routes,
        private readonly DispatchRepositoryInterface $dispatches,
    ) {}

    /** Paradas pendientes o con el vehículo ya en el sitio. */
    public function openStops(Route $route): int
    {
        return $this->routes->openStopsCount($route);
    }

    /**
     * Despachos asignados a la ruta cuya entrega sigue abierta. Un despacho
     * anulado ya no cuenta, y uno entregado —o rechazado, o devuelto— tampoco:
     * su viaje terminó.
     *
     * @return array<int, Dispatch>
     */
    public function pendingDispatches(Route $route, ?string $dispatchDate = null): array
    {
        $result = $this->dispatches->search(new SearchDispatchCommand(
            filters: array_filter([
                'route_id' => $route->id,
                'date_from' => $dispatchDate,
                'date_to' => $dispatchDate,
            ]),
            limit: self::MAX_DISPATCHES,
            companyId: $route->company_id,
        ));

        return array_values(array_filter(
            $result['data'],
            static fn (Dispatch $dispatch): bool => $dispatch->status !== 'cancelled'
                && ! $dispatch->isDeliverySettled(),
        ));
    }

    /**
     * Peso y volumen que la carga del día le pide al vehículo.
     *
     * @param  array<int, Dispatch>  $dispatches
     * @return array{weight: float, volume: float}
     */
    public function load(array $dispatches): array
    {
        $weight = 0.0;
        $volume = 0.0;

        foreach ($dispatches as $dispatch) {
            $weight += (float) $dispatch->total_weight;
            $volume += (float) $dispatch->total_volume;
        }

        return [
            'weight' => round($weight, 4),
            'volume' => round($volume, 4),
        ];
    }
}
