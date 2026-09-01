<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Repositories;

use App\Modules\ClientAdvance\Commands\CreateClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\SearchClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\UpdateClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\UpdateStatusClientAdvanceCommand;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use Illuminate\Support\Facades\DB;

class ClientAdvanceRepository extends ClientAdvanceFilters implements ClientAdvanceRepositoryInterface
{
    public function create(CreateClientAdvanceCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($command, $rates): void {
            ClientAdvance::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_id' => $command->clientId,
                'sales_order_id' => $command->salesOrderId,
                'advance_date' => $command->advanceDate,
                'payment_method' => $command->paymentMethod,
                'reference' => $command->reference,
                'bank_account' => $command->bankAccount,
                ...$rates->toAttributes(),
                ...$this->amounts($command->amount),
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?ClientAdvance
    {
        return ClientAdvance::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): ClientAdvance
    {
        return ClientAdvance::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(ClientAdvance $model, UpdateClientAdvanceCommand $command, DocumentRatesData $rates): void
    {
        /** Lo aplicado a facturas y la marca de anulación no se editan aquí. */
        $model->update([
            'client_id' => $command->clientId,
            'sales_order_id' => $command->salesOrderId,
            'advance_date' => $command->advanceDate,
            'payment_method' => $command->paymentMethod,
            'reference' => $command->reference,
            'bank_account' => $command->bankAccount,
            ...$rates->toAttributes(),
            ...$this->amounts(
                $command->amount,
                (float) $model->applied_amount,
                (float) $model->refunded_amount,
            ),
            'notes' => $command->notes,
        ]);
    }

    public function updateStatus(ClientAdvance $model, UpdateStatusClientAdvanceCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $model->update($attributes);
    }

    public function lockById(string $id, ?string $companyId = null): ?ClientAdvance
    {
        return ClientAdvance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    /**
     * @return array{ data: ClientAdvance[], total: int }
     */
    public function search(SearchClientAdvanceCommand $command): array
    {
        $query = ClientAdvance::query()
            ->with(['client'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('advance_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return ['client', 'salesOrder', 'collection'];
    }

    /**
     * Importes del anticipo. El disponible es lo recibido menos lo que ya se
     * llevaron las facturas y lo que se le devolvió al cliente; editar el monto
     * no toca ni lo aplicado ni lo devuelto.
     *
     * @return array<string, float>
     */
    private function amounts(float $amount, float $appliedAmount = 0, float $refundedAmount = 0): array
    {
        $amount = round($amount, 2);

        return [
            'amount' => $amount,
            'balance' => round($amount - $appliedAmount - $refundedAmount, 2),
        ];
    }

    /**
     * Generate the next sequential per-company code (ANC000001, ANC000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = ClientAdvance::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', ClientAdvance::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(ClientAdvance::CODE_PREFIX))) + 1
            : 1;

        return ClientAdvance::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
