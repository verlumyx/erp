<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseInvoice\Commands\SearchPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Resources\PurchaseInvoiceOptionResource;
use App\Modules\PurchaseInvoice\Resources\PurchaseInvoiceResource;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceFindService;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceFormOptionsService;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceOptionSearchService;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceSearchService;
use Illuminate\Http\JsonResponse;
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

    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    /**
     * Estados que el select ofrece: solo una factura viva se puede acreditar.
     * Una anulada ya no debe nada y una en borrador todavía no debe.
     */
    private const LOOKUP_STATUSES = 'confirmed,completed';

    public function __construct(
        private readonly PurchaseInvoiceSearchService $searchService,
        private readonly PurchaseInvoiceFindService $findService,
        private readonly PurchaseInvoiceFormOptionsService $formOptionsService,
        private readonly PurchaseInvoiceOptionSearchService $optionSearchService,
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

    /**
     * Página de opciones para el select remoto de facturas de compra.
     *
     * Devuelve JSON, no Inertia: la consume `Select2Ajax` por `fetch`. La usa
     * la nota de crédito a proveedor para elegir la factura afectada sin cargar
     * todas las facturas de la empresa en sus props.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('purchase-invoices.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchPurchaseInvoiceCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'supplier_id' => $request->string('supplier_id')->toString(),
                /** Los pagos piden solo lo que sigue debiendo saldo. */
                'open' => $request->string('open')->toString(),
                /*
                 * Buscar ofrece solo las facturas que se pueden acreditar;
                 * hidratar lo ya elegido no filtra por estado: una factura que
                 * después cambió de estado sigue siendo la de su nota.
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
                fn (PurchaseInvoice $invoice): array => (new PurchaseInvoiceOptionResource($invoice))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
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
