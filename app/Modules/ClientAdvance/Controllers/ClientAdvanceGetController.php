<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientAdvance\Commands\SearchClientAdvanceCommand;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Resources\ClientAdvanceOptionResource;
use App\Modules\ClientAdvance\Resources\ClientAdvanceResource;
use App\Modules\ClientAdvance\Services\ClientAdvanceFindService;
use App\Modules\ClientAdvance\Services\ClientAdvanceSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientAdvanceGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'client_id', 'sales_order_id', 'reference',
        'payment_method', 'status', 'date_from', 'date_to',
    ];

    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    /**
     * Estados que el select ofrece: solo un anticipo recibido tiene crédito
     * que aplicar. Uno comprometido todavía no recibió nada.
     */
    private const LOOKUP_STATUSES = 'confirmed,partial';

    public function __construct(
        private readonly ClientAdvanceSearchService $searchService,
        private readonly ClientAdvanceFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('client-advances.list') ?? false, 403);

        $command = new SearchClientAdvanceCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('client-advances/index', [
            'clientAdvances' => array_map(
                fn (ClientAdvance $advance): array => (new ClientAdvanceResource($advance))->resolve(),
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
        abort_unless($request->user()?->hasPermission('client-advances.create') ?? false, 403);

        return Inertia::render('client-advances/create');
    }

    /**
     * Página de opciones para el select remoto de anticipos.
     *
     * Devuelve JSON, no Inertia: la consume `Select2Ajax` por `fetch`. La usará
     * el cobro para elegir qué anticipo aplica a una factura sin cargar todos
     * los anticipos de la empresa en sus props.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('client-advances.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchClientAdvanceCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'client_id' => $request->string('client_id')->toString(),
                /** Los cobros piden solo lo que todavía tiene crédito. */
                'open' => $request->string('open')->toString(),
                /*
                 * Buscar ofrece solo los anticipos aplicables; hidratar lo ya
                 * elegido no filtra por estado: un anticipo que después se
                 * agotó sigue siendo el de su documento.
                 */
                'statuses' => $ids === ''
                    ? $request->string('statuses', self::LOOKUP_STATUSES)->toString()
                    : $request->string('statuses')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->searchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (ClientAdvance $advance): array => (new ClientAdvanceOptionResource($advance))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('client-advances.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('client-advances/show', [
            'clientAdvance' => (new ClientAdvanceResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('client-advances.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('client-advances/edit', [
            'clientAdvance' => (new ClientAdvanceResource($model))->resolve(),
        ]);
    }
}
