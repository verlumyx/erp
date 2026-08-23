<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseReturn\Commands\SearchPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Resources\PurchaseReturnOptionResource;
use App\Modules\PurchaseReturn\Resources\PurchaseReturnResource;
use App\Modules\PurchaseReturn\Services\PurchaseReturnFindService;
use App\Modules\PurchaseReturn\Services\PurchaseReturnFormOptionsService;
use App\Modules\PurchaseReturn\Services\PurchaseReturnOptionSearchService;
use App\Modules\PurchaseReturn\Services\PurchaseReturnSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'supplier_id', 'purchase_invoice_id', 'warehouse_id',
        'tracking_number', 'reason', 'status', 'date_from', 'date_to',
    ];

    /** Tamaño de página del select remoto. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly PurchaseReturnSearchService $searchService,
        private readonly PurchaseReturnFindService $findService,
        private readonly PurchaseReturnFormOptionsService $formOptionsService,
        private readonly PurchaseReturnOptionSearchService $optionSearchService,
    ) {}

    /**
     * Devoluciones como opciones de un select remoto: la nota de crédito elige
     * aquí la que acredita, y el padrón es demasiado grande para viajar entero
     * en las props de esa pantalla.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('purchase-returns.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchPurchaseReturnCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'supplier_id' => $request->string('supplier_id')->toString(),
                /*
                 * Buscar ofrece solo lo que se puede acreditar; hidratar lo ya
                 * elegido no filtra por estado: una devolución ya acreditada
                 * sigue siendo la de su nota.
                 */
                'creditable' => $ids === ''
                    ? $request->string('creditable', 'yes')->toString()
                    : $request->string('creditable')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (PurchaseReturn $return): array => (new PurchaseReturnOptionResource($return))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('purchase-returns.list') ?? false, 403);

        $command = new SearchPurchaseReturnCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('purchase-returns/index', [
            'purchaseReturns' => array_map(
                fn (PurchaseReturn $return): array => (new PurchaseReturnResource($return))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTERS, 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('purchase-returns.create') ?? false, 403);

        return Inertia::render('purchase-returns/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-returns.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-returns/show', [
            'purchaseReturn' => (new PurchaseReturnResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-returns.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-returns/edit', [
            'purchaseReturn' => (new PurchaseReturnResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
