<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Adjustment\Commands\UpdateAdjustmentCommand;
use App\Modules\Adjustment\Requests\UpdateAdjustmentRequest;
use App\Modules\Adjustment\Services\AdjustmentUpdateService;
use Illuminate\Http\RedirectResponse;

class AdjustmentPutController extends Controller
{
    public function __construct(
        private readonly AdjustmentUpdateService $updateService,
    ) {}

    public function __invoke(UpdateAdjustmentRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateAdjustmentCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('adjustments.show', ['company' => $company, 'id' => $id]);
    }
}
