<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LoadPlan\Commands\UpdateStatusLoadPlanCommand;
use App\Modules\LoadPlan\Requests\UpdateStatusLoadPlanRequest;
use App\Modules\LoadPlan\Services\LoadPlanUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class LoadPlanUpdateStatusController extends Controller
{
    public function __construct(
        private readonly LoadPlanUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusLoadPlanRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusLoadPlanCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('load-plans.show', [
            'company' => $company,
            'id' => $id,
        ])->with('success', 'Estado del plan de carga actualizado exitosamente.');
    }
}
