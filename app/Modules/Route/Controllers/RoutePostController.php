<?php

declare(strict_types=1);

namespace App\Modules\Route\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Route\Commands\CreateRouteCommand;
use App\Modules\Route\Requests\CreateRouteRequest;
use App\Modules\Route\Services\RouteCreateService;
use Illuminate\Http\RedirectResponse;

class RoutePostController extends Controller
{
    public function __construct(
        private readonly RouteCreateService $createService,
    ) {}

    public function __invoke(CreateRouteRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateRouteCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('routes.index', ['company' => $request->route('company')]);
    }
}
