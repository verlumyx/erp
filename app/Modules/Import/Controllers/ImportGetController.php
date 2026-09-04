<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Import\Commands\SearchImportCommand;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Resources\ImportEntryOptionResource;
use App\Modules\Import\Resources\ImportResource;
use App\Modules\Import\Services\ImportEntryOptionSearchService;
use App\Modules\Import\Services\ImportFindService;
use App\Modules\Import\Services\ImportFormOptionsService;
use App\Modules\Import\Services\ImportSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'warehouse_id', 'reference', 'allocation_method',
        'currency', 'status', 'date_from', 'date_to',
    ];

    /** Tamaño de página del select remoto. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly ImportSearchService $searchService,
        private readonly ImportFindService $findService,
        private readonly ImportFormOptionsService $formOptionsService,
        private readonly ImportEntryOptionSearchService $entryOptionService,
    ) {}

    /**
     * Las recepciones que este expediente puede costear.
     *
     * No es el `lookup` de entradas: aquí solo caben las confirmadas de su
     * bodega y sin otro expediente vivo detrás. El padrón es demasiado grande
     * para viajar entero en las props de la pantalla.
     *
     * Se llama `entries` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function entries(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('imports.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchEntryCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'warehouse_id' => $request->string('warehouse_id')->toString(),
                /*
                 * Buscar ofrece solo lo que todavía se puede costear; hidratar
                 * lo ya elegido no filtra por estado: una entrada que este
                 * expediente tomó sigue siendo la suya.
                 */
                'statuses' => $ids === '' ? implode(',', Entry::POSTED_STATUSES) : '',
            ],
            limit: $perPage,
            offset: ($page - 1) * $perPage,
            companyId: $company,
        );

        $result = $this->entryOptionService->execute(
            $command,
            $request->string('import_id')->toString() ?: null,
        );

        return response()->json([
            'data' => array_map(
                fn (Entry $entry): array => (new ImportEntryOptionResource($entry))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('imports.list') ?? false, 403);

        $command = new SearchImportCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('imports/index', [
            'imports' => array_map(
                fn (Import $import): array => (new ImportResource($import))->resolve(),
                $result['data'],
            ),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only([...self::FILTERS, 'limit', 'offset']),
            /** Las bodegas alimentan el filtro del listado; son pocas por empresa. */
            'warehouses' => $this->formOptionsService->warehouses(session('current_company_id')),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('imports.create') ?? false, 403);

        return Inertia::render('imports/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('imports.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('imports/show', [
            'import' => (new ImportResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('imports.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('imports/edit', [
            'import' => (new ImportResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
