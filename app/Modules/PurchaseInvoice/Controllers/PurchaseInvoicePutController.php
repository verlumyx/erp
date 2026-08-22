<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseInvoice\Commands\UpdatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Requests\UpdatePurchaseInvoiceRequest;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceUpdateService;
use Illuminate\Http\RedirectResponse;

class PurchaseInvoicePutController extends Controller
{
    public function __construct(
        private readonly PurchaseInvoiceUpdateService $updateService,
    ) {}

    public function __invoke(UpdatePurchaseInvoiceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdatePurchaseInvoiceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('purchase-invoices.show', ['company' => $company, 'id' => $id]);
    }
}
