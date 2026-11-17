<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vehicle\Commands\UpdateVehicleCommand;
use App\Modules\Vehicle\Requests\UpdateVehicleRequest;
use App\Modules\Vehicle\Services\VehicleUpdateService;
use Illuminate\Http\RedirectResponse;

class VehiclePutController extends Controller
{
    public function __construct(
        private readonly VehicleUpdateService $updateService,
    ) {}

    public function __invoke(UpdateVehicleRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateVehicleCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('vehicles.show', [
            'company' => $company,
            'id' => $id,
        ]);
    }
}
