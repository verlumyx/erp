<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientCollection\Commands\SearchClientCollectionCommand;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Resources\ClientCollectionResource;
use App\Modules\ClientCollection\Services\ClientCollectionFindService;
use App\Modules\ClientCollection\Services\ClientCollectionFormOptionsService;
use App\Modules\ClientCollection\Services\ClientCollectionSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientCollectionGetController extends Controller
{
    /** Claves de filtro aceptadas por el listado. */
    private const FILTERS = [
        'code', 'client_id', 'reference', 'payment_method', 'collected_by',
        'check_status', 'origin_type', 'status', 'date_from', 'date_to',
    ];

    public function __construct(
        private readonly ClientCollectionSearchService $searchService,
        private readonly ClientCollectionFindService $findService,
        private readonly ClientCollectionFormOptionsService $formOptionsService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('client-collections.list') ?? false, 403);

        $command = new SearchClientCollectionCommand(
            filters: $request->only(self::FILTERS),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('client-collections/index', [
            'clientCollections' => array_map(
                fn (ClientCollection $collection): array => (new ClientCollectionResource($collection))->resolve(),
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
        abort_unless($request->user()?->hasPermission('client-collections.create') ?? false, 403);

        return Inertia::render('client-collections/create', [
            'options' => $this->formOptionsService->execute(session('current_company_id')),
        ]);
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('client-collections.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('client-collections/show', [
            'clientCollection' => (new ClientCollectionResource($model))->resolve(),
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('client-collections.update') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('client-collections/edit', [
            'clientCollection' => (new ClientCollectionResource($model))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
