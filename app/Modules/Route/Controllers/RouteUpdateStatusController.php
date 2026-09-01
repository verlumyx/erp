<?php

declare(strict_types=1);

namespace App\Modules\Route\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Route\Commands\UpdateStatusRouteCommand;
use App\Modules\Route\Requests\UpdateStatusRouteRequest;
use App\Modules\Route\Services\RouteUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class RouteUpdateStatusController extends Controller
{
    public function __construct(
        private readonly RouteUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusRouteRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusRouteCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('routes.show', ['company' => $company, 'id' => $id]);
    }
}
