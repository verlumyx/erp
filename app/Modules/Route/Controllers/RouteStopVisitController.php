<?php

declare(strict_types=1);

namespace App\Modules\Route\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Route\Commands\RegisterRouteStopVisitCommand;
use App\Modules\Route\Requests\RegisterRouteStopVisitRequest;
use App\Modules\Route\Services\RouteStopVisitService;
use Illuminate\Http\RedirectResponse;

/**
 * Registra el resultado de una visita. Vuelve al detalle en la fecha de la
 * parada, no en la de hoy: se registra el día que se recorrió.
 */
class RouteStopVisitController extends Controller
{
    public function __construct(
        private readonly RouteStopVisitService $visitService,
    ) {}

    public function __invoke(
        RegisterRouteStopVisitRequest $request,
        string $company,
        string $id,
        string $stop,
    ): RedirectResponse {
        $registered = $this->visitService->execute(
            $id,
            $stop,
            RegisterRouteStopVisitCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('routes.show', [
            'company' => $company,
            'id' => $id,
            'stop_date' => $registered->stop_date?->toDateString(),
        ]);
    }
}
