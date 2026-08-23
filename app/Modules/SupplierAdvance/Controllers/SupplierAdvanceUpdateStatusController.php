<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierAdvance\Commands\UpdateStatusSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Requests\UpdateStatusSupplierAdvanceRequest;
use App\Modules\SupplierAdvance\Services\SupplierAdvanceUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class SupplierAdvanceUpdateStatusController extends Controller
{
    public function __construct(
        private readonly SupplierAdvanceUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusSupplierAdvanceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusSupplierAdvanceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('supplier-advances.show', ['company' => $company, 'id' => $id]);
    }
}
