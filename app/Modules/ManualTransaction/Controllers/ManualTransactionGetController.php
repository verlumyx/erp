<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ManualTransaction\Commands\SearchManualTransactionCommand;
use App\Modules\ManualTransaction\Resources\ManualTransactionResource;
use App\Modules\ManualTransaction\Services\ManualTransactionFindService;
use App\Modules\ManualTransaction\Services\ManualTransactionSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ManualTransactionGetController extends Controller
{
    public function __construct(
        private readonly ManualTransactionSearchService $searchService,
        private readonly ManualTransactionFindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('manual-transactions.list') ?? false, 403);

        $companyId = session('current_company_id');

        $filters = $request->only(['code', 'reference', 'date_from', 'date_to']);

        $command = new SearchManualTransactionCommand(
            filters: $filters,
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $companyId,
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('manual-transactions/index', [
            'manualTransactions' => ManualTransactionResource::collection($result['data'])->resolve(),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => array_merge($filters, $request->only(['limit', 'offset'])),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('manual-transactions.create') ?? false, 403);

        return Inertia::render('manual-transactions/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('manual-transactions.show') ?? false, 403);

        $model = $this->findService->execute($id, $company);

        return Inertia::render('manual-transactions/show', [
            'manualTransaction' => (new ManualTransactionResource($model))->resolve(),
        ]);
    }
}
