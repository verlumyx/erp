<?php

declare(strict_types=1);

namespace App\Modules\Entry\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Resources\EntryOptionResource;
use App\Modules\Entry\Resources\EntryResource;
use App\Modules\Entry\Services\EntryFindService;
use App\Modules\Entry\Services\EntryFormOptionsService;
use App\Modules\Entry\Services\EntryOptionSearchService;
use App\Modules\Entry\Services\EntrySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EntryGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'supplier_id', 'warehouse_id', 'entry_type', 'inspection_status',
        'supplier_document', 'is_invoiced', 'status', 'date_from', 'date_to',
    ];

    /** Tamaño de página del select remoto. */
    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    public function __construct(
        private readonly EntrySearchService $searchService,
        private readonly EntryFindService $findService,
        private readonly EntryFormOptionsService $formOptionsService,
        private readonly EntryOptionSearchService $optionSearchService,
    ) {}

    /**
     * Entradas como opciones de un select remoto: un documento que respalda una
     * recepción elige aquí la suya, y el padrón es demasiado grande para viajar
     * entero en las props de esa pantalla.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('entries.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchEntryCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'supplier_id' => $request->string('supplier_id')->toString(),
                /*
                 * Buscar ofrece solo lo que todavía se puede respaldar;
                 * hidratar lo ya elegido no filtra por estado: una entrada ya
                 * facturada sigue siendo la de su factura.
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
                fn (Entry $entry): array => (new EntryOptionResource($entry))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('entries.list') ?? false, 403);

        $command = new SearchEntryCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('entries/index', [
            'entries' => array_map(
                fn (Entry $entry): array => (new EntryResource($entry))->resolve(),
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
        abort_unless($request->user()?->hasPermission('entries.create') ?? false, 403);

        return Inertia::render('entries/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('entries.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('entries/show', [
            'entry' => (new EntryResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('entries.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('entries/edit', [
            'entry' => (new EntryResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
