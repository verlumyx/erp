<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MeasurementUnit\Commands\CreateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Requests\CreateMeasurementUnitRequest;
use App\Modules\MeasurementUnit\Services\MeasurementUnitCreateService;
use Illuminate\Http\RedirectResponse;

class MeasurementUnitPostController extends Controller
{
    public function __construct(
        private readonly MeasurementUnitCreateService $createService,
    ) {}

    public function __invoke(CreateMeasurementUnitRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateMeasurementUnitCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('measurement-units.index', ['company' => $request->route('company')]);
    }
}
