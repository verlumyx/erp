<?php

declare(strict_types=1);

namespace App\Modules\Plan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plan\Commands\UpdateStatusPlanCommand;
use App\Modules\Plan\Requests\UpdateStatusPlanRequest;
use App\Modules\Plan\Services\PlanUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class PlanUpdateStatusController extends Controller
{
    public function __construct(
        private readonly PlanUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusPlanRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusPlanCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('plans.index', ['company' => $company])
            ->with('success', 'Estado del plan actualizado correctamente.');
    }
}
