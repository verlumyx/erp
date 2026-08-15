<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transaction\Commands\SearchTransactionCommand;
use App\Modules\Transaction\Resources\TransactionResource;
use App\Modules\Transaction\Services\TransactionSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MovementGetController extends Controller
{
    public function __construct(
        private readonly TransactionSearchService $searchService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('reports.movements') ?? false, 403);

        $command = new SearchTransactionCommand(
            filters: $request->only(['type', 'category', 'date_from', 'date_to']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: session('current_company_id'),
        );

        $searched = $request->boolean('searched');

        $result = $searched
            ? $this->searchService->execute($command)
            : ['data' => [], 'total' => 0];

        return Inertia::render('reports/movements/index', [
            'searched' => $searched,
            'movements' => TransactionResource::collection($result['data'])->resolve(),
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => $request->only(['type', 'category', 'date_from', 'date_to', 'searched', 'limit', 'offset']),
        ]);
    }
}
