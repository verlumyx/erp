<?php

declare(strict_types=1);

namespace App\Modules\Plan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plan\Commands\CreatePlanCommand;
use App\Modules\Plan\Requests\CreatePlanRequest;
use App\Modules\Plan\Services\PlanCreateService;
use Illuminate\Http\RedirectResponse;

class PlanPostController extends Controller
{
    public function __construct(
        private readonly PlanCreateService $createService,
    ) {}

    public function __invoke(CreatePlanRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreatePlanCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('plans.index', ['company' => $request->route('company')]);
    }
}
