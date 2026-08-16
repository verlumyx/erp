<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MeasurementUnit\Commands\UpdateStatusMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Requests\UpdateStatusMeasurementUnitRequest;
use App\Modules\MeasurementUnit\Services\MeasurementUnitUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class MeasurementUnitUpdateStatusController extends Controller
{
    public function __construct(
        private readonly MeasurementUnitUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusMeasurementUnitRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusMeasurementUnitCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('measurement-units.index', ['company' => $company])
            ->with('success', 'Estado de la unidad de medida actualizado correctamente.');
    }
}
