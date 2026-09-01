<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Adjustment\Commands\UpdateStatusAdjustmentCommand;
use App\Modules\Adjustment\Requests\UpdateStatusAdjustmentRequest;
use App\Modules\Adjustment\Services\AdjustmentUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class AdjustmentUpdateStatusController extends Controller
{
    public function __construct(
        private readonly AdjustmentUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusAdjustmentRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusAdjustmentCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('adjustments.show', ['company' => $company, 'id' => $id]);
    }
}
