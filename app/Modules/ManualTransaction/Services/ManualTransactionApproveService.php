<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Services;

use App\Modules\ManualTransaction\Exceptions\InvalidManualTransactionStatusException;
use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Repositories\Contracts\ManualTransactionRepositoryInterface;
use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualTransactionApproveService
{
    public function __construct(
        private readonly ManualTransactionRepositoryInterface $repository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function execute(string $id, ?string $companyId, ?string $resolvedBy): ManualTransaction
    {
        $model = $this->repository->findOrFail($id, $companyId);

        if (! $model->canBeApproved()) {
            throw new InvalidManualTransactionStatusException;
        }

        DB::transaction(function () use ($model, $resolvedBy): void {
            $this->recordLedgerEntries($model, $resolvedBy);

            $this->repository->approve($model);
        });

        return $this->repository->findOrFail($id, $companyId);
    }

    /**
     * Vuelca cada línea aprobada al libro contable (app_transactions), una
     * transacción por línea, ligada por `related`. Cruza el límite del módulo
     * Transaction a través de su contrato publicado.
     */
    private function recordLedgerEntries(ManualTransaction $model, ?string $resolvedBy): void
    {
        $model->loadMissing('lines');

        foreach ($model->lines as $line) {
            $this->transactionRepository->create(new CreateTransactionCommand(
                id: Str::uuid7()->toString(),
                companyId: $model->company_id,
                type: $line->type,
                category: $line->category,
                amount: (float) $line->amount,
                date: $model->date->toDateString(),
                paymentMethod: $model->payment_method,
                description: $line->description ?? $model->description ?? "Transacción manual {$model->code}",
                recordedBy: $resolvedBy,
                relatedType: 'ManualTransaction',
                relatedId: $model->id,
                currency: $model->currency,
                reference: $model->reference,
            ));
        }
    }
}
