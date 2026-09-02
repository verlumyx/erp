<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesCreditNote\Commands\SearchSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Resources\SalesCreditNoteOptionResource;
use App\Modules\SalesCreditNote\Resources\SalesCreditNoteResource;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteFindService;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteFormOptionsService;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesCreditNoteGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'client_id', 'sales_invoice_id', 'note_number', 'note_series',
        'reason', 'status', 'date_from', 'date_to',
    ];

    private const LOOKUP_PER_PAGE = 20;

    private const LOOKUP_MAX_PER_PAGE = 50;

    /**
     * Estados que el select ofrece: solo una nota confirmada tiene crédito que
     * aplicar. Una en borrador todavía no acreditó nada.
     */
    private const LOOKUP_STATUSES = 'confirmed';

    public function __construct(
        private readonly SalesCreditNoteSearchService $searchService,
        private readonly SalesCreditNoteFindService $findService,
        private readonly SalesCreditNoteFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('sales-credit-notes.list') ?? false, 403);

        $command = new SearchSalesCreditNoteCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('sales-credit-notes/index', [
            'salesCreditNotes' => array_map(
                fn (SalesCreditNote $note): array => (new SalesCreditNoteResource($note))->resolve(),
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
        abort_unless($request->user()?->hasPermission('sales-credit-notes.create') ?? false, 403);

        return Inertia::render('sales-credit-notes/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    /**
     * Opciones para el select remoto (`Select2Ajax`) del cobro o del pago: las
     * notas con crédito disponible.
     *
     * Se llama `lookup` y no `options` porque Wayfinder nombra la función
     * generada como la ruta, y ahí `options` choca con su propio parámetro de
     * query: el TypeScript generado no compila.
     */
    public function lookup(Request $request, string $company): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('sales-credit-notes.list') ?? false, 403);

        $perPage = min(max($request->integer('per_page', self::LOOKUP_PER_PAGE), 1), self::LOOKUP_MAX_PER_PAGE);
        $page = max($request->integer('page', 1), 1);
        $ids = $request->string('ids')->toString();

        $command = new SearchSalesCreditNoteCommand(
            filters: [
                'q' => $request->string('q')->toString(),
                'ids' => $ids,
                'client_id' => $request->string('client_id')->toString(),
                /** Los cobros y los pagos piden solo lo que todavía tiene crédito. */
                'open' => $request->string('open')->toString(),
                /*
                 * Buscar ofrece solo las notas aplicables; hidratar lo ya
                 * elegido no filtra por estado: una nota que después se agotó
                 * sigue siendo la de su documento.
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
                fn (SalesCreditNote $note): array => (new SalesCreditNoteOptionResource($note))->resolve(),
                $result['data'],
            ),
            'has_more' => $result['total'] > $command->offset + $command->limit,
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales-credit-notes.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales-credit-notes/show', [
            'salesCreditNote' => (new SalesCreditNoteResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('sales-credit-notes.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('sales-credit-notes/edit', [
            'salesCreditNote' => (new SalesCreditNoteResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
