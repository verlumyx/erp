<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Repositories;

use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Commands\SearchTransactionCommand;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository extends TransactionFilters implements TransactionRepositoryInterface
{
    public function create(CreateTransactionCommand $command): Transaction
    {
        return Transaction::create([
            'id' => $command->id,
            'company_id' => $command->companyId,
            'type' => $command->type,
            'category' => $command->category,
            'subcategory' => $command->subcategory,
            'related_type' => $command->relatedType,
            'related_id' => $command->relatedId,
            'amount' => $command->amount,
            'currency' => $command->currency,
            'date' => $command->date,
            'payment_method' => $command->paymentMethod,
            'reference' => $command->reference,
            'period_from' => $command->periodFrom,
            'period_to' => $command->periodTo,
            'description' => $command->description,
            'notes' => $command->notes,
            'recorded_by' => $command->recordedBy,
            'receipt_url' => $command->receiptUrl,
        ]);
    }

    public function findById(string $id, ?string $companyId = null): ?Transaction
    {
        return Transaction::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Transaction
    {
        return Transaction::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * @return array{ data: Transaction[], total: int }
     */
    public function search(SearchTransactionCommand $command): array
    {
        $query = Transaction::query()
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array{ total_income: float, total_expense: float, income_count: int, expense_count: int }
     */
    public function summarize(SearchTransactionCommand $command): array
    {
        $query = Transaction::query()
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $rows = $query
            ->selectRaw('type, COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $income = $rows->get(Transaction::TYPE_INCOME);
        $expense = $rows->get(Transaction::TYPE_EXPENSE);

        return [
            'total_income' => (float) ($income->total ?? 0),
            'total_expense' => (float) ($expense->total ?? 0),
            'income_count' => (int) ($income->count ?? 0),
            'expense_count' => (int) ($expense->count ?? 0),
        ];
    }
}
