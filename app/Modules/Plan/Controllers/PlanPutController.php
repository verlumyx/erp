<?php

declare(strict_types=1);

namespace App\Modules\Plan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plan\Commands\UpdatePlanCommand;
use App\Modules\Plan\Requests\UpdatePlanRequest;
use App\Modules\Plan\Services\PlanUpdateService;
use Illuminate\Http\RedirectResponse;

class PlanPutController extends Controller
{
    public function __construct(
        private readonly PlanUpdateService $updateService,
    ) {}

    public function __invoke(UpdatePlanRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdatePlanCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('plans.show', ['company' => $company, 'id' => $id]);
    }
}
