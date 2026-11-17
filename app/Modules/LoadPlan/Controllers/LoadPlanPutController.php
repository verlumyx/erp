<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LoadPlan\Commands\UpdateLoadPlanCommand;
use App\Modules\LoadPlan\Requests\UpdateLoadPlanRequest;
use App\Modules\LoadPlan\Services\LoadPlanUpdateService;
use Illuminate\Http\RedirectResponse;

class LoadPlanPutController extends Controller
{
    public function __construct(
        private readonly LoadPlanUpdateService $updateService,
    ) {}

    public function __invoke(UpdateLoadPlanRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateLoadPlanCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('load-plans.show', [
            'company' => $company,
            'id' => $id,
        ])->with('success', 'Plan de carga actualizado exitosamente.');
    }
}
