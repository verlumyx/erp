<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesOrder\Commands\SearchSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Resources\SalesOrderInvoiceableLineResource;
use App\Modules\SalesOrder\Resources\SalesOrderOptionResource;
use App\Modules\SalesOrder\Resources\SalesOrderResource;
use App\Modules\SalesOrder\Services\SalesOrderFindService;
use App\Modules\SalesOrder\Services\SalesOrderFormOptionsService;
use App\Modules\SalesOrder\Services\SalesOrderInvoiceableLinesService;
use App\Modules\SalesOrder\Services\SalesOrderOptionSearchService;
use App\Modules\SalesOrder\Services\SalesOrderSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderGetController extends Controller
{
    /** Claves de filtro que acepta el listado. */
    private const FILTER_KEYS = [
        'code', 'client', 'client_id', 'warehouse_id', 'salesperson_id',
        'client_reference', 'currency', 'order_date_from', 'order_date_to', 'status',
    ];

    /** Tamaño de página del select remoto y su tope. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 100;

    public function __construct(
        private readonly SalesOrderSearchService $searchService,
        private readonly SalesOrderFindService $findService,
        private readonly SalesOrderFormOptionsService $formOptionsService,
        private readonly SalesOrderOptionSearchService $optionSearchService,
        private readonly SalesOrderInvoiceableLinesService $invoiceableLinesService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('sales-orders.list') ?? false, 403);

        $command = new SearchSalesOrderCommand(
            filters: $request->only(self::FILTER_KEYS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('sales-orders/index', [
            'salesOrders' => array_map(
                fn (SalesOrder $order): array => (new SalesOrderResource($order))->resolve(),
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
        abort_unless($request->user()?->hasPermission('sales-orders.create') ?? false, 403);

        return Inertia::render('sales-orders/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    /**
     * Página de opciones para el select remoto de pedidos.
     *
     * Devuelve JSON, no Inertia: la consume `Select2Ajax` por `fetch` desde la
     * pantalla de la factura, que arma sus líneas con las del pedido elegido.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('sales-orders.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchSalesOrderCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                /** Los anticipos piden solo los pedidos del cliente elegido. */
                'client_id' => $request->string('client_id')->toString(),
                /*
                 * Buscar ofrece solo pedidos que todavía admiten el documento
                 * que los pide —factura por defecto, despacho si lo pide la
                 * pantalla de despachos—; hidratar lo ya elegido no filtra: un
                 * pedido cumplido después sigue siendo el origen del documento
                 * que se está editando.
                 */
                'invoiceable' => $ids === '' && ! $request->boolean('dispatchable') ? 'yes' : '',
                'dispatchable' => $ids === '' && $request->boolean('dispatchable') ? 'yes' : '',
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (SalesOrder $order): array => (new SalesOrderOptionResource($order))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    /**
     * Las líneas de un pedido que todavía admiten factura.
     *
     * Devuelve JSON, no Inertia: la pide la pantalla de la factura de venta en
     * cuanto se elige el pedido, y con ella arma sus líneas. Va aparte del
     * `lookup` a propósito: el saldo por facturar solo interesa del pedido
     * elegido, y meterlo en cada opción del select engordaría el menú entero.
     */
    public function invoiceableLines(Request $request, string $company, string $id): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('sales-orders.list') ?? false, 403);

        $lines = $this->invoiceableLinesService->execute($id, $company);

        return response()->json([
            'data' => array_map(
                fn (SalesOrderLine $line): array => (new SalesOrderInvoiceableLineResource($line))->resolve(),
                $lines,
            ),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales-orders.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales-orders/show', [
            'salesOrder' => (new SalesOrderResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales-orders.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales-orders/edit', [
            'salesOrder' => (new SalesOrderResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
