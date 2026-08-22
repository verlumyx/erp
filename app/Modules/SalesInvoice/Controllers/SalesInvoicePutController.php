<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesInvoice\Commands\UpdateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Requests\UpdateSalesInvoiceRequest;
use App\Modules\SalesInvoice\Services\SalesInvoiceUpdateService;
use Illuminate\Http\RedirectResponse;

class SalesInvoicePutController extends Controller
{
    public function __construct(
        private readonly SalesInvoiceUpdateService $updateService,
    ) {}

    public function __invoke(UpdateSalesInvoiceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateSalesInvoiceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('sales-invoices.show', ['company' => $company, 'id' => $id]);
    }
}
