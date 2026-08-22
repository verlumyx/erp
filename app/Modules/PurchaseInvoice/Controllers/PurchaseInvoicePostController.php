<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseInvoice\Commands\CreatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Requests\CreatePurchaseInvoiceRequest;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceCreateService;
use Illuminate\Http\RedirectResponse;

class PurchaseInvoicePostController extends Controller
{
    public function __construct(
        private readonly PurchaseInvoiceCreateService $createService,
    ) {}

    public function __invoke(CreatePurchaseInvoiceRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreatePurchaseInvoiceCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('purchase-invoices.index', ['company' => $request->route('company')]);
    }
}
