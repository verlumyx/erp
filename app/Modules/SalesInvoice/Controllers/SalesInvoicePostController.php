<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesInvoice\Commands\CreateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Requests\CreateSalesInvoiceRequest;
use App\Modules\SalesInvoice\Services\SalesInvoiceCreateService;
use Illuminate\Http\RedirectResponse;

class SalesInvoicePostController extends Controller
{
    public function __construct(
        private readonly SalesInvoiceCreateService $createService,
    ) {}

    public function __invoke(CreateSalesInvoiceRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateSalesInvoiceCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('sales-invoices.index', ['company' => $request->route('company')]);
    }
}
