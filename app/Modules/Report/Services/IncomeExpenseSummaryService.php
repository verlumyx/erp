<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Modules\Transaction\Commands\SearchTransactionCommand;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;

class IncomeExpenseSummaryService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $repository,
    ) {}

    /**
     * Totaliza ingresos y gastos de una compañía según los filtros del command.
     *
     * @return array{ total_income: float, total_expense: float, balance: float, income_count: int, expense_count: int }
     */
    public function execute(SearchTransactionCommand $command): array
    {
        $totals = $this->repository->summarize($command);

        return [
            'total_income' => $totals['total_income'],
            'total_expense' => $totals['total_expense'],
            'balance' => $totals['total_income'] - $totals['total_expense'],
            'income_count' => $totals['income_count'],
            'expense_count' => $totals['expense_count'],
        ];
    }
}
