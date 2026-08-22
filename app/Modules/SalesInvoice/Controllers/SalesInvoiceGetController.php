<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesInvoice\Commands\SearchSalesInvoiceCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Resources\SalesInvoiceResource;
use App\Modules\SalesInvoice\Services\SalesInvoiceFindService;
use App\Modules\SalesInvoice\Services\SalesInvoiceFormOptionsService;
use App\Modules\SalesInvoice\Services\SalesInvoiceSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesInvoiceGetController extends Controller
{
    /** Claves de filtro que acepta el listado. */
    private const FILTER_KEYS = [
        'code', 'client', 'client_id', 'warehouse_id', 'salesperson_id',
        'invoice_number', 'invoice_series', 'currency', 'sale_type',
        'payment_status', 'invoice_date_from', 'invoice_date_to',
        'due_date_from', 'due_date_to', 'status',
    ];

    public function __construct(
        private readonly SalesInvoiceSearchService $searchService,
        private readonly SalesInvoiceFindService $findService,
        private readonly SalesInvoiceFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('sales-invoices.list') ?? false, 403);

        $command = new SearchSalesInvoiceCommand(
            filters: $request->only(self::FILTER_KEYS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('sales-invoices/index', [
            'salesInvoices' => array_map(
                fn (SalesInvoice $invoice): array => (new SalesInvoiceResource($invoice))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTER_KEYS, 'limit', 'offset']),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('sales-invoices.create') ?? false, 403);

        return Inertia::render('sales-invoices/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales-invoices.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales-invoices/show', [
            'salesInvoice' => (new SalesInvoiceResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales-invoices.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales-invoices/edit', [
            'salesInvoice' => (new SalesInvoiceResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
