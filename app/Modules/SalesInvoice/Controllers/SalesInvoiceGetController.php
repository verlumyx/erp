<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesInvoice\Commands\SearchSalesInvoiceCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Resources\SalesInvoiceOptionResource;
use App\Modules\SalesInvoice\Resources\SalesInvoiceResource;
use App\Modules\SalesInvoice\Services\SalesInvoiceFindService;
use App\Modules\SalesInvoice\Services\SalesInvoiceFormOptionsService;
use App\Modules\SalesInvoice\Services\SalesInvoiceOptionSearchService;
use App\Modules\SalesInvoice\Services\SalesInvoiceSearchService;
use Illuminate\Http\JsonResponse;
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

    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    /**
     * Estados que el select ofrece: solo una factura viva se puede cobrar. Una
     * anulada ya no debe nada y una en borrador todavía no debe.
     */
    private const LOOKUP_STATUSES = 'confirmed,completed';

    public function __construct(
        private readonly SalesInvoiceSearchService $searchService,
        private readonly SalesInvoiceFindService $findService,
        private readonly SalesInvoiceFormOptionsService $formOptionsService,
        private readonly SalesInvoiceOptionSearchService $optionSearchService,
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

    /**
     * Página de opciones para el select remoto de facturas de venta.
     *
     * Devuelve JSON, no Inertia: la consume `Select2Ajax` por `fetch`. La usa
     * el cobro a cliente para elegir la factura que se abona sin cargar todas
     * las facturas de la empresa en sus props.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('sales-invoices.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchSalesInvoiceCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'client_id' => $request->string('client_id')->toString(),
                /** Los cobros piden solo lo que sigue debiendo saldo. */
                'open' => $request->string('open')->toString(),
                /*
                 * Buscar ofrece solo las facturas que se pueden cobrar;
                 * hidratar lo ya elegido no filtra por estado: una factura que
                 * después cambió de estado sigue siendo la de su cobro.
                 */
                'statuses' => $ids === ''
                    ? $request->string('statuses', self::LOOKUP_STATUSES)->toString()
                    : $request->string('statuses')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (SalesInvoice $invoice): array => (new SalesInvoiceOptionResource($invoice))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
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
