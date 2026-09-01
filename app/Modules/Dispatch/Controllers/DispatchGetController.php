<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Resources\DispatchOptionResource;
use App\Modules\Dispatch\Resources\DispatchResource;
use App\Modules\Dispatch\Services\DispatchFindService;
use App\Modules\Dispatch\Services\DispatchFormOptionsService;
use App\Modules\Dispatch\Services\DispatchOptionSearchService;
use App\Modules\Dispatch\Services\DispatchSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DispatchGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'client_id', 'warehouse_id', 'driver_id', 'route_id',
        'tracking_number', 'delivery_status', 'status', 'date_from', 'date_to',
    ];

    /** Tamaño de página del select remoto. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly DispatchSearchService $searchService,
        private readonly DispatchFindService $findService,
        private readonly DispatchFormOptionsService $formOptionsService,
        private readonly DispatchOptionSearchService $optionSearchService,
    ) {}

    /**
     * Despachos como opciones de un select remoto: la factura de venta elige
     * aquí el que va a facturar, y el padrón es demasiado grande para viajar
     * entero en las props de esa pantalla.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dispatches.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchDispatchCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'client_id' => $request->string('client_id')->toString(),
                /*
                 * Buscar ofrece solo lo que se puede facturar; hidratar lo ya
                 * elegido no filtra por estado: un despacho anulado después
                 * sigue siendo el de la factura que se está editando.
                 */
                'invoiceable' => $ids === ''
                    ? $request->string('invoiceable', 'yes')->toString()
                    : $request->string('invoiceable')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (Dispatch $dispatch): array => (new DispatchOptionResource($dispatch))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('dispatches.list') ?? false, 403);

        $command = new SearchDispatchCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('dispatches/index', [
            'dispatches' => array_map(
                fn (Dispatch $dispatch): array => (new DispatchResource($dispatch))->resolve(),
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
        abort_unless($request->user()?->hasPermission('dispatches.create') ?? false, 403);

        return Inertia::render('dispatches/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('dispatches.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('dispatches/show', [
            'dispatch' => (new DispatchResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('dispatches.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('dispatches/edit', [
            'dispatch' => (new DispatchResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
