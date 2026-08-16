<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WarehouseLocation\Commands\UpdateStatusWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Requests\UpdateStatusWarehouseLocationRequest;
use App\Modules\WarehouseLocation\Services\WarehouseLocationUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class WarehouseLocationUpdateStatusController extends Controller
{
    public function __construct(
        private readonly WarehouseLocationUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusWarehouseLocationRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusWarehouseLocationCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('warehouse-locations.index', ['company' => $company])
            ->with('success', 'Estado de la ubicación actualizado correctamente.');
    }
}
