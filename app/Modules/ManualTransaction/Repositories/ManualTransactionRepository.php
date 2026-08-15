<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Repositories;

use App\Modules\ManualTransaction\Commands\CreateManualTransactionCommand;
use App\Modules\ManualTransaction\Commands\SearchManualTransactionCommand;
use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Models\ManualTransactionLine;
use App\Modules\ManualTransaction\Repositories\Contracts\ManualTransactionRepositoryInterface;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualTransactionRepository extends ManualTransactionFilters implements ManualTransactionRepositoryInterface
{
    public function create(CreateManualTransactionCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $total = array_sum(array_map(
                static fn (array $line): float => (float) $line['amount'],
                $command->lines,
            ));

            $manualTransaction = ManualTransaction::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'date' => $command->date,
                'payment_method' => $command->paymentMethod,
                'reference' => $command->reference,
                'currency' => $command->currency,
                'description' => $command->description,
                'notes' => $command->notes,
                'recorded_by' => $command->recordedBy,
                'total' => $total,
            ]);

            foreach ($command->lines as $line) {
                ManualTransactionLine::create([
                    'id' => Str::uuid7()->toString(),
                    'manual_transaction_id' => $manualTransaction->id,
                    'type' => Transaction::typeForCategory($line['category']),
                    'category' => $line['category'],
                    'amount' => $line['amount'],
                    'description' => $line['description'] ?? null,
                ]);
            }
        });
    }

    /**
     * Marca la transacción manual como aprobada. El volcado al libro contable
     * (app_transactions) lo orquesta ManualTransactionApproveService, que cruza
     * el límite del módulo Transaction; este repositorio solo persiste su agregado.
     */
    public function approve(ManualTransaction $model): void
    {
        $model->update([
            'status' => ManualTransaction::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function cancel(ManualTransaction $model): void
    {
        $model->update([
            'status' => ManualTransaction::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    public function findById(string $id, ?string $companyId = null): ?ManualTransaction
    {
        return ManualTransaction::query()
            ->with($this->showRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): ManualTransaction
    {
        return ManualTransaction::query()
            ->with($this->showRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * @return array{ data: ManualTransaction[], total: int }
     */
    public function search(SearchManualTransactionCommand $command): array
    {
        $query = ManualTransaction::query()
            ->with(['lines', 'recordedBy'])
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
     * Relaciones cargadas para la pantalla de detalle.
     *
     * @return array<int|string, mixed>
     */
    private function showRelations(): array
    {
        return [
            'recordedBy',
            'lines' => fn ($q) => $q->orderBy('created_at'),
        ];
    }

    /**
     * Genera el siguiente código secuencial por compañía (MTX000001, MTX000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = ManualTransaction::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', ManualTransaction::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(ManualTransaction::CODE_PREFIX))) + 1
            : 1;

        return ManualTransaction::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
