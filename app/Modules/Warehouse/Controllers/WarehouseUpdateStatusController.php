<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Warehouse\Commands\UpdateStatusWarehouseCommand;
use App\Modules\Warehouse\Requests\UpdateStatusWarehouseRequest;
use App\Modules\Warehouse\Services\WarehouseUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class WarehouseUpdateStatusController extends Controller
{
    public function __construct(
        private readonly WarehouseUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusWarehouseRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusWarehouseCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('warehouses.index', ['company' => $company])
            ->with('success', 'Estado de la bodega actualizado correctamente.');
    }
}
