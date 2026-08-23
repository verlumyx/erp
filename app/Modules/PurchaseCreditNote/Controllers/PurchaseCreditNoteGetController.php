<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseCreditNote\Commands\SearchPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Resources\PurchaseCreditNoteResource;
use App\Modules\PurchaseCreditNote\Services\PurchaseCreditNoteFindService;
use App\Modules\PurchaseCreditNote\Services\PurchaseCreditNoteFormOptionsService;
use App\Modules\PurchaseCreditNote\Services\PurchaseCreditNoteSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseCreditNoteGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'supplier_id', 'purchase_invoice_id', 'supplier_document_number',
        'reason', 'status', 'date_from', 'date_to',
    ];

    public function __construct(
        private readonly PurchaseCreditNoteSearchService $searchService,
        private readonly PurchaseCreditNoteFindService $findService,
        private readonly PurchaseCreditNoteFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('purchase-credit-notes.list') ?? false, 403);

        $command = new SearchPurchaseCreditNoteCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('purchase-credit-notes/index', [
            'purchaseCreditNotes' => array_map(
                fn (PurchaseCreditNote $note): array => (new PurchaseCreditNoteResource($note))->resolve(),
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
        abort_unless($request->user()?->hasPermission('purchase-credit-notes.create') ?? false, 403);

        return Inertia::render('purchase-credit-notes/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-credit-notes.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-credit-notes/show', [
            'purchaseCreditNote' => (new PurchaseCreditNoteResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('purchase-credit-notes.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('purchase-credit-notes/edit', [
            'purchaseCreditNote' => (new PurchaseCreditNoteResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
