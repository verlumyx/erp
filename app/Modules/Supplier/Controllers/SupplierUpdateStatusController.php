<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Supplier\Commands\UpdateStatusSupplierCommand;
use App\Modules\Supplier\Requests\UpdateStatusSupplierRequest;
use App\Modules\Supplier\Services\SupplierUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class SupplierUpdateStatusController extends Controller
{
    public function __construct(
        private readonly SupplierUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusSupplierRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusSupplierCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('suppliers.index', ['company' => $company])
            ->with('success', 'Estado del proveedor actualizado correctamente.');
    }
}
