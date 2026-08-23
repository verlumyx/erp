<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Repositories;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SupplierAdvance\Commands\CreateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\SearchSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\UpdateStatusSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\UpdateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SupplierAdvanceRepository extends SupplierAdvanceFilters implements SupplierAdvanceRepositoryInterface
{
    public function create(CreateSupplierAdvanceCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($command, $rates): void {
            SupplierAdvance::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'supplier_id' => $command->supplierId,
                'purchase_order_id' => $command->purchaseOrderId,
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

    public function findById(string $id, ?string $companyId = null): ?SupplierAdvance
    {
        return SupplierAdvance::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): SupplierAdvance
    {
        return SupplierAdvance::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(SupplierAdvance $model, UpdateSupplierAdvanceCommand $command, DocumentRatesData $rates): void
    {
        /** Lo aplicado a facturas y la marca de anulación no se editan aquí. */
        $model->update([
            'supplier_id' => $command->supplierId,
            'purchase_order_id' => $command->purchaseOrderId,
            'advance_date' => $command->advanceDate,
            'payment_method' => $command->paymentMethod,
            'reference' => $command->reference,
            'bank_account' => $command->bankAccount,
            ...$rates->toAttributes(),
            ...$this->amounts($command->amount, (float) $model->applied_amount),
            'notes' => $command->notes,
        ]);
    }

    public function updateStatus(SupplierAdvance $model, UpdateStatusSupplierAdvanceCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $model->update($attributes);
    }

    public function lockById(string $id, ?string $companyId = null): ?SupplierAdvance
    {
        return SupplierAdvance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    /**
     * @return array{ data: SupplierAdvance[], total: int }
     */
    public function search(SearchSupplierAdvanceCommand $command): array
    {
        $query = SupplierAdvance::query()
            ->with(['supplier'])
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
        return ['supplier', 'purchaseOrder', 'payment'];
    }

    /**
     * Importes del anticipo. El disponible es lo entregado menos lo que ya se
     * llevaron las facturas; editar el monto no toca lo aplicado.
     *
     * @return array<string, float>
     */
    private function amounts(float $amount, float $appliedAmount = 0): array
    {
        $amount = round($amount, 2);

        return [
            'amount' => $amount,
            'balance' => round($amount - $appliedAmount, 2),
        ];
    }

    /**
     * Generate the next sequential per-company code (ANP000001, ANP000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = SupplierAdvance::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', SupplierAdvance::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(SupplierAdvance::CODE_PREFIX))) + 1
            : 1;

        return SupplierAdvance::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
