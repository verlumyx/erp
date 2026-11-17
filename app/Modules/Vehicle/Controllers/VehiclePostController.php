<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vehicle\Commands\CreateVehicleCommand;
use App\Modules\Vehicle\Requests\CreateVehicleRequest;
use App\Modules\Vehicle\Services\VehicleCreateService;
use Illuminate\Http\RedirectResponse;

class VehiclePostController extends Controller
{
    public function __construct(
        private readonly VehicleCreateService $createService,
    ) {}

    public function __invoke(CreateVehicleRequest $request): RedirectResponse
    {
        $vehicle = $this->createService->execute(
            CreateVehicleCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('vehicles.show', [
            'company' => $request->route('company'),
            'id' => $vehicle->id,
        ]);
    }
}
