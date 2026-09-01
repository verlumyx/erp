<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Adjustment\Commands\CreateAdjustmentCommand;
use App\Modules\Adjustment\Requests\CreateAdjustmentRequest;
use App\Modules\Adjustment\Services\AdjustmentCreateService;
use Illuminate\Http\RedirectResponse;

class AdjustmentPostController extends Controller
{
    public function __construct(
        private readonly AdjustmentCreateService $createService,
    ) {}

    public function __invoke(CreateAdjustmentRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateAdjustmentCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('adjustments.index', ['company' => $request->route('company')]);
    }
}
