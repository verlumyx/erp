<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MeasurementUnit\Commands\UpdateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Requests\UpdateMeasurementUnitRequest;
use App\Modules\MeasurementUnit\Services\MeasurementUnitUpdateService;
use Illuminate\Http\RedirectResponse;

class MeasurementUnitPutController extends Controller
{
    public function __construct(
        private readonly MeasurementUnitUpdateService $updateService,
    ) {}

    public function __invoke(UpdateMeasurementUnitRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateMeasurementUnitCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('measurement-units.show', ['company' => $company, 'id' => $id]);
    }
}
