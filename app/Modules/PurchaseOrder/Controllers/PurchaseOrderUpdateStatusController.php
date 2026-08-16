<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseOrder\Commands\UpdateStatusPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Requests\UpdateStatusPurchaseOrderRequest;
use App\Modules\PurchaseOrder\Services\PurchaseOrderUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class PurchaseOrderUpdateStatusController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusPurchaseOrderRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusPurchaseOrderCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('purchase-orders.show', ['company' => $company, 'id' => $id]);
    }
}
