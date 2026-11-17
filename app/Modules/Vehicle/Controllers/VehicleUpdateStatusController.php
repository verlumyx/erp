<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vehicle\Commands\UpdateStatusVehicleCommand;
use App\Modules\Vehicle\Requests\UpdateStatusVehicleRequest;
use App\Modules\Vehicle\Services\VehicleUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class VehicleUpdateStatusController extends Controller
{
    public function __construct(
        private readonly VehicleUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusVehicleRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusVehicleCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('vehicles.show', ['company' => $company, 'id' => $id]);
    }
}
