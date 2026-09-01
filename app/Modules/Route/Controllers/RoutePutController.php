<?php

declare(strict_types=1);

namespace App\Modules\Route\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Route\Commands\UpdateRouteCommand;
use App\Modules\Route\Requests\UpdateRouteRequest;
use App\Modules\Route\Services\RouteUpdateService;
use Illuminate\Http\RedirectResponse;

class RoutePutController extends Controller
{
    public function __construct(
        private readonly RouteUpdateService $updateService,
    ) {}

    public function __invoke(UpdateRouteRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateRouteCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('routes.show', ['company' => $company, 'id' => $id]);
    }
}
