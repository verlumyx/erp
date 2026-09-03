<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseOrder\Commands\SearchPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Resources\PurchaseOrderInvoiceableLineResource;
use App\Modules\PurchaseOrder\Resources\PurchaseOrderOptionResource;
use App\Modules\PurchaseOrder\Resources\PurchaseOrderResource;
use App\Modules\PurchaseOrder\Services\PurchaseOrderFindService;
use App\Modules\PurchaseOrder\Services\PurchaseOrderFormOptionsService;
use App\Modules\PurchaseOrder\Services\PurchaseOrderInvoiceableLinesService;
use App\Modules\PurchaseOrder\Services\PurchaseOrderOptionSearchService;
use App\Modules\PurchaseOrder\Services\PurchaseOrderSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'supplier_id', 'warehouse_id', 'supplier_reference', 'status', 'date_from', 'date_to',
    ];

    /** Tamaño de página del select remoto. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    /**
     * Estados que puede facturar una orden: la que sigue en borrador todavía no
     * comprometió nada, y la cerrada o anulada ya no admite factura nueva.
     */
    private const LOOKUP_STATUSES = 'confirmed,partial';

    public function __construct(
        private readonly PurchaseOrderSearchService $searchService,
        private readonly PurchaseOrderFindService $findService,
        private readonly PurchaseOrderFormOptionsService $formOptionsService,
        private readonly PurchaseOrderOptionSearchService $optionSearchService,
        private readonly PurchaseOrderInvoiceableLinesService $invoiceableLinesService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('purchase-orders.list') ?? false, 403);

        $command = new SearchPurchaseOrderCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('purchase-orders/index', [
            'purchaseOrders' => array_map(
                fn (PurchaseOrder $order): array => (new PurchaseOrderResource($order))->resolve(),
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
        abort_unless($request->user()?->hasPermission('purchase-orders.create') ?? false, 403);

        return Inertia::render('purchase-orders/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    /**
     * Página de opciones para el select remoto de órdenes de compra.
     *
     * Devuelve JSON, no Inertia: la consume `Select2Ajax` por `fetch`. La usa
     * la factura de compra para elegir el documento origen sin cargar todas las
     * órdenes de la empresa en sus props.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('purchase-orders.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchPurchaseOrderCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'supplier_id' => $request->string('supplier_id')->toString(),
                /*
                 * Buscar ofrece solo las órdenes que se pueden facturar;
                 * hidratar lo ya elegido no filtra por estado: una orden
                 * completada después sigue siendo el origen de su factura.
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
                fn (PurchaseOrder $order): array => (new PurchaseOrderOptionResource($order))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    /**
     * Las líneas de una orden que todavía admiten factura.
     *
     * Devuelve JSON, no Inertia: la pide la pantalla de la factura de compra en
     * cuanto se elige la orden, y con ella arma sus líneas. Va aparte del
     * `lookup` a propósito: el saldo por facturar solo interesa de la orden
     * elegida, y meterlo en cada opción del select engordaría el menú entero.
     */
    public function invoiceableLines(Request $request, string $company, string $id): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('purchase-orders.list') ?? false, 403);

        $lines = $this->invoiceableLinesService->execute($id, $company);

        return response()->json([
            'data' => array_map(
                fn (PurchaseOrderLine $line): array => (new PurchaseOrderInvoiceableLineResource($line))->resolve(),
                $lines,
            ),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-orders.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-orders/show', [
            'purchaseOrder' => (new PurchaseOrderResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-orders.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-orders/edit', [
            'purchaseOrder' => (new PurchaseOrderResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
