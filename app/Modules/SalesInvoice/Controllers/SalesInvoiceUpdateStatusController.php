<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesInvoice\Commands\UpdateStatusSalesInvoiceCommand;
use App\Modules\SalesInvoice\Requests\UpdateStatusSalesInvoiceRequest;
use App\Modules\SalesInvoice\Services\SalesInvoiceUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class SalesInvoiceUpdateStatusController extends Controller
{
    public function __construct(
        private readonly SalesInvoiceUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusSalesInvoiceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusSalesInvoiceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('sales-invoices.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Estado de la factura actualizado correctamente.');
    }
}
