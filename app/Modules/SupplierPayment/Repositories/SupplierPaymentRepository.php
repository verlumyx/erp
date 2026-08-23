<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Repositories;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SupplierPayment\Commands\CreateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\PostSupplierPaymentApplicationCommand;
use App\Modules\SupplierPayment\Commands\SearchSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\SupplierPaymentApplicationData;
use App\Modules\SupplierPayment\Commands\UpdateStatusSupplierPaymentCommand;
use App\Modules\SupplierPayment\Commands\UpdateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SupplierPaymentRepository extends SupplierPaymentFilters implements SupplierPaymentRepositoryInterface
{
    public function create(CreateSupplierPaymentCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($command, $rates): void {
            $payment = SupplierPayment::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'supplier_id' => $command->supplierId,
                'origin_type' => $command->originType,
                'origin_id' => $command->originId,
                'payment_date' => $command->paymentDate,
                'payment_method' => $command->paymentMethod,
                'reference' => $command->reference,
                'bank_account' => $command->bankAccount,
                ...$rates->toAttributes(),
                ...$this->totals($command->amount, $command->withholdingAmount, $command->applications, $rates),
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncApplications($payment, $command->applications, $command->createdBy);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?SupplierPayment
    {
        return SupplierPayment::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): SupplierPayment
    {
        return SupplierPayment::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(SupplierPayment $model, UpdateSupplierPaymentCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($model, $command, $rates): void {
            /** El origen se congeló al crear el pago: no se reescribe aquí. */
            $model->update([
                'supplier_id' => $command->supplierId,
                'payment_date' => $command->paymentDate,
                'payment_method' => $command->paymentMethod,
                'reference' => $command->reference,
                'bank_account' => $command->bankAccount,
                ...$rates->toAttributes(),
                ...$this->totals($command->amount, $command->withholdingAmount, $command->applications, $rates),
                'notes' => $command->notes,
            ]);

            $this->syncApplications($model, $command->applications, $model->created_by);
        });
    }

    public function updateStatus(SupplierPayment $model, UpdateStatusSupplierPaymentCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
            $attributes['cancellation_reason'] = $command->cancellationReason;
        }

        $model->update($attributes);
    }

    /**
     * @return array<int, SupplierPaymentApplication>
     */
    public function activeApplications(SupplierPayment $model): array
    {
        return SupplierPaymentApplication::query()
            ->where('source_type', SupplierPayment::APPLICATION_SOURCE)
            ->where('source_id', $model->id)
            ->where('status', 'active')
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    public function postApplication(
        SupplierPaymentApplication $application,
        PostSupplierPaymentApplicationCommand $command,
    ): void {
        $application->update([
            'applied_at' => $command->appliedAt,
            'exchange_rate' => $command->exchangeRate,
            'exchange_difference' => $command->exchangeDifference,
            'status' => 'active',
        ]);
    }

    public function reverseApplication(SupplierPaymentApplication $application): void
    {
        $application->update(['status' => 'reversed']);
    }

    /**
     * @return array{ data: SupplierPayment[], total: int }
     */
    public function search(SearchSupplierPaymentCommand $command): array
    {
        $query = SupplierPayment::query()
            ->with(['supplier'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('payment_date')
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
        return ['supplier', 'applications.purchaseInvoice'];
    }

    /**
     * Alinea el reparto con lo enviado desde la pantalla.
     *
     * La fila se reconoce por la factura que abona, no por un id del cliente:
     * la tabla es única por `(factura, origen)`, así que un pago abona una
     * factura una sola vez y corregir el monto reescribe esa misma fila. Las
     * que dejan de venir no se borran, se revierten.
     *
     * Escribir la fila no mueve todavía el saldo de la factura: eso lo hace el
     * pago al confirmarse.
     *
     * @param  array<int, SupplierPaymentApplicationData>  $applications
     */
    private function syncApplications(SupplierPayment $payment, array $applications, ?string $createdBy): void
    {
        $existing = SupplierPaymentApplication::query()
            ->where('source_type', SupplierPayment::APPLICATION_SOURCE)
            ->where('source_id', $payment->id)
            ->get()
            ->keyBy('purchase_invoice_id');

        $keep = [];

        foreach ($this->activeRows($applications) as $row) {
            $attributes = [
                'company_id' => $payment->company_id,
                'applied_amount' => $row->appliedAmount,
                'applied_at' => now(),
                'exchange_rate' => $payment->exchange_rate,
                'status' => 'active',
            ];

            $current = $existing->get($row->purchaseInvoiceId);

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = SupplierPaymentApplication::create([
                ...$attributes,
                'purchase_invoice_id' => $row->purchaseInvoiceId,
                'source_type' => SupplierPayment::APPLICATION_SOURCE,
                'source_id' => $payment->id,
                'exchange_difference' => 0,
                'created_by' => $createdBy,
            ])->id;
        }

        SupplierPaymentApplication::query()
            ->where('source_type', SupplierPayment::APPLICATION_SOURCE)
            ->where('source_id', $payment->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'reversed']);
    }

    /**
     * Importes de la cabecera.
     *
     * Lo que el pago puede saldar no es solo lo que salió del banco: la
     * retención practicada también cancela deuda, aunque se entere al fisco en
     * vez de pagarse al proveedor. Por eso el excedente sin aplicar se mide
     * contra `amount + withholding_amount`; sin retención —el caso normal— la
     * cuenta es exactamente `amount - applied_amount`.
     *
     * @param  array<int, SupplierPaymentApplicationData>  $applications
     * @return array<string, float>
     */
    private function totals(
        float $amount,
        float $withholdingAmount,
        array $applications,
        DocumentRatesData $rates,
    ): array {
        $amount = round($amount, 2);
        $withholding = round($withholdingAmount, 2);
        $applied = $this->appliedTotal($applications);

        return [
            'amount' => $amount,
            'withholding_amount' => $withholding,
            'applied_amount' => $applied,
            'unapplied_amount' => round($amount + $withholding - $applied, 2),
            /** El pago tiene valor legal: lo que salió en bolívares queda escrito. */
            'amount_ves' => round($amount * $rates->exchangeRate, $rates->amountDecimals),
        ];
    }

    /**
     * @param  array<int, SupplierPaymentApplicationData>  $applications
     */
    private function appliedTotal(array $applications): float
    {
        return round(array_sum(array_map(
            static fn (SupplierPaymentApplicationData $row): float => $row->appliedAmount,
            $this->activeRows($applications),
        )), 2);
    }

    /**
     * @param  array<int, SupplierPaymentApplicationData>  $applications
     * @return array<int, SupplierPaymentApplicationData>
     */
    private function activeRows(array $applications): array
    {
        return array_values(array_filter(
            $applications,
            static fn (SupplierPaymentApplicationData $row): bool => $row->status === 'active',
        ));
    }

    /**
     * Generate the next sequential per-company code (PGP000001, PGP000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = SupplierPayment::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', SupplierPayment::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(SupplierPayment::CODE_PREFIX))) + 1
            : 1;

        return SupplierPayment::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
