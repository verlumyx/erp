<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LoadPlan\Commands\CreateLoadPlanCommand;
use App\Modules\LoadPlan\Requests\CreateLoadPlanRequest;
use App\Modules\LoadPlan\Services\LoadPlanCreateService;
use Illuminate\Http\RedirectResponse;

class LoadPlanPostController extends Controller
{
    public function __construct(
        private readonly LoadPlanCreateService $createService,
    ) {}

    public function __invoke(CreateLoadPlanRequest $request): RedirectResponse
    {
        $companyId = session('current_company_id');

        $loadPlan = $this->createService->execute(
            CreateLoadPlanCommand::fromRequest($request, $companyId)
        );

        return redirect()->route('load-plans.show', [
            'company' => $request->route('company'),
            'id' => $loadPlan->id,
        ])->with('success', 'Plan de carga creado exitosamente.');
    }
}
