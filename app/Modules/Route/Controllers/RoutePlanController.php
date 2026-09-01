<?php

declare(strict_types=1);

namespace App\Modules\Route\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Route\Commands\PlanRouteCommand;
use App\Modules\Route\Requests\PlanRouteRequest;
use App\Modules\Route\Services\RoutePlanService;
use Illuminate\Http\RedirectResponse;

/**
 * Genera las paradas de un día. Vuelve al detalle mostrando justo esa fecha:
 * lo que se acaba de planificar es lo que hay que revisar.
 */
class RoutePlanController extends Controller
{
    public function __construct(
        private readonly RoutePlanService $planService,
    ) {}

    public function __invoke(PlanRouteRequest $request, string $company, string $id): RedirectResponse
    {
        $command = PlanRouteCommand::fromRequest($request);

        $this->planService->execute($id, $command, $company);

        return redirect()->route('routes.show', [
            'company' => $company,
            'id' => $id,
            'stop_date' => $command->stopDate,
        ]);
    }
}
