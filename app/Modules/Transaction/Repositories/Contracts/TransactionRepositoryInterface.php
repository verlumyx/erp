<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Repositories\Contracts;

use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Commands\SearchTransactionCommand;
use App\Modules\Transaction\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function create(CreateTransactionCommand $command): Transaction;

    public function findById(string $id, ?string $companyId = null): ?Transaction;

    public function findOrFail(string $id, ?string $companyId = null): Transaction;

    /** @return array{ data: Transaction[], total: int } */
    public function search(SearchTransactionCommand $command): array;

    /** @return array{ total_income: float, total_expense: float, income_count: int, expense_count: int } */
    public function summarize(SearchTransactionCommand $command): array;
}
