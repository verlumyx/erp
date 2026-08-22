<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseInvoice\Commands\SearchPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Resources\PurchaseInvoiceResource;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceFindService;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceFormOptionsService;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'supplier_id', 'warehouse_id', 'supplier_invoice_number',
        'status', 'payment_status', 'date_from', 'date_to',
    ];

    public function __construct(
        private readonly PurchaseInvoiceSearchService $searchService,
        private readonly PurchaseInvoiceFindService $findService,
        private readonly PurchaseInvoiceFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('purchase-invoices.list') ?? false, 403);

        $command = new SearchPurchaseInvoiceCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('purchase-invoices/index', [
            'purchaseInvoices' => array_map(
                fn (PurchaseInvoice $invoice): array => (new PurchaseInvoiceResource($invoice))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTERS, 'limit', 'offset']),
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('purchase-invoices.create') ?? false, 403);

        return Inertia::render('purchase-invoices/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-invoices.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-invoices/show', [
            'purchaseInvoice' => (new PurchaseInvoiceResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-invoices.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-invoices/edit', [
            'purchaseInvoice' => (new PurchaseInvoiceResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
