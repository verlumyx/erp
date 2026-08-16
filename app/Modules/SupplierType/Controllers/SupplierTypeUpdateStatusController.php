<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierType\Commands\UpdateStatusSupplierTypeCommand;
use App\Modules\SupplierType\Requests\UpdateStatusSupplierTypeRequest;
use App\Modules\SupplierType\Services\SupplierTypeUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class SupplierTypeUpdateStatusController extends Controller
{
    public function __construct(
        private readonly SupplierTypeUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusSupplierTypeRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusSupplierTypeCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('supplier-types.index', ['company' => $company])
            ->with('success', 'Estado del tipo de proveedor actualizado correctamente.');
    }
}
