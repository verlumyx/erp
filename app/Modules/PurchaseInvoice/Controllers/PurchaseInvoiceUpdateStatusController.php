<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseInvoice\Commands\UpdateStatusPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Requests\UpdateStatusPurchaseInvoiceRequest;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class PurchaseInvoiceUpdateStatusController extends Controller
{
    public function __construct(
        private readonly PurchaseInvoiceUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusPurchaseInvoiceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusPurchaseInvoiceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('purchase-invoices.show', ['company' => $company, 'id' => $id]);
    }
}
