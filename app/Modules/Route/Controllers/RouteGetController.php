<?php

declare(strict_types=1);

namespace App\Modules\Route\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Route\Commands\SearchRouteCommand;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Models\RouteStop;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use App\Modules\Route\Resources\RouteOptionResource;
use App\Modules\Route\Resources\RouteResource;
use App\Modules\Route\Resources\RouteStopResource;
use App\Modules\Route\Services\RouteFindService;
use App\Modules\Route\Services\RouteFormOptionsService;
use App\Modules\Route\Services\RouteOptionSearchService;
use App\Modules\Route\Services\RouteSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RouteGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'name', 'type', 'frequency', 'warehouse_id', 'driver_id',
        'salesperson_id', 'zone', 'city', 'client_id', 'status',
    ];

    /** Tamaño de página del select remoto. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly RouteSearchService $searchService,
        private readonly RouteFindService $findService,
        private readonly RouteFormOptionsService $formOptionsService,
        private readonly RouteOptionSearchService $optionSearchService,
        private readonly RouteRepositoryInterface $repository,
    ) {}

    /**
     * Rutas como opciones de un select remoto, para el despacho o el traslado
     * que necesita señalar una sin descargar el padrón entero en sus props.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('routes.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);

        $command = new SearchRouteCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $request->string('ids')->toString(),
                'type' => $request->string('type')->toString(),
                'warehouse_id' => $request->string('warehouse_id')->toString(),
                'driver_id' => $request->string('driver_id')->toString(),
                'client_id' => $request->string('client_id')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->optionSearchService->execute($command);

        return response()->json([
            'data' => array_map(
                fn (Route $route): array => (new RouteOptionResource($route))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('routes.list') ?? false, 403);

        $command = new SearchRouteCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('routes/index', [
            'routes' => array_map(
                fn (Route $route): array => (new RouteResource($route))->resolve(),
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
        abort_unless($request->user()?->hasPermission('routes.create') ?? false, 403);

        return Inertia::render('routes/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    /**
     * El detalle muestra el recorrido de un día. Sin fecha en la query se
     * enseña el de hoy, que es el que le interesa a quien abre la pantalla.
     */
    public function show(string $company, string $id): Response
    {
        $request = request();

        abort_unless($request->user()?->hasPermission('routes.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        $stopDate = $request->filled('stop_date')
            ? $request->string('stop_date')->toString()
            : now()->toDateString();

        return Inertia::render('routes/show', [
            'route' => (new RouteResource($model))->resolve(),
            'stop_date' => $stopDate,
            'stops' => array_map(
                fn (RouteStop $stop): array => (new RouteStopResource($stop))->resolve(),
                $this->repository->stopsOn($model, $stopDate),
            ),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('routes.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('routes/edit', [
            'route' => (new RouteResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
