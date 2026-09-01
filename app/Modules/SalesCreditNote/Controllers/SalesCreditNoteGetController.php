<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesCreditNote\Commands\SearchSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Resources\SalesCreditNoteResource;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteFindService;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteFormOptionsService;
use App\Modules\SalesCreditNote\Services\SalesCreditNoteSearchService;
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
