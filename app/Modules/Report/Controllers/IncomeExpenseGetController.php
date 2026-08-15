<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\IncomeExpenseSummaryService;
use App\Modules\Transaction\Commands\SearchTransactionCommand;
use App\Modules\Transaction\Resources\TransactionResource;
use App\Modules\Transaction\Services\TransactionSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeExpenseGetController extends Controller
{
    public function __construct(
        private readonly TransactionSearchService $searchService,
        private readonly IncomeExpenseSummaryService $summaryService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('reports.income_expenses') ?? false, 403);

        $dateFrom = $request->date('date_from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->date('date_to')?->toDateString() ?? now()->toDateString();

        $companyId = (string) session('current_company_id');

        $command = new SearchTransactionCommand(
            filters: ['date_from' => $dateFrom, 'date_to' => $dateTo],
            limit: $request->integer('limit', 50),
            offset: $request->integer('offset', 0),
            companyId: $companyId,
        );

        $searched = $request->boolean('searched');

        $result = $searched
            ? $this->searchService->execute($command)
            : ['data' => [], 'total' => 0];

        $summary = $searched
            ? $this->summaryService->execute($command)
            : ['total_income' => 0.0, 'total_expense' => 0.0, 'balance' => 0.0, 'income_count' => 0, 'expense_count' => 0];

        return Inertia::render('reports/income-expenses/index', [
            'searched' => $searched,
            'movements' => TransactionResource::collection($result['data'])->resolve(),
            'summary' => $summary,
            'meta' => [
                'total' => $result['total'],
                'limit' => $command->limit,
                'offset' => $command->offset,
                'has_more' => $result['total'] > $command->offset + $command->limit,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'limit' => $command->limit,
                'offset' => $command->offset,
            ],
        ]);
    }
}
