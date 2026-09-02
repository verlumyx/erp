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
use App\Modules\SupplierPayment\Commands\WriteSupplierPaymentApplicationCommand;
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
                'credit_source_id' => $this->creditSource($command->paymentMethod, $command->creditSourceId),
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
                'credit_source_id' => $this->creditSource($command->paymentMethod, $command->creditSourceId),
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
            ->where('source_type', $model->applicationSource())
            ->where('source_id', $model->applicationSourceId())
            ->where('status', 'active')
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    /**
     * Las aplicaciones vivas de cualquier origen —pago, anticipo o nota de
     * crédito—, en el orden en que se escribieron.
     *
     * @return array<int, SupplierPaymentApplication>
     */
    public function applicationsOf(string $sourceType, string $sourceId): array
    {
        return SupplierPaymentApplication::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'active')
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    public function findApplication(
        string $sourceType,
        string $sourceId,
        string $purchaseInvoiceId,
    ): ?SupplierPaymentApplication {
        return SupplierPaymentApplication::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('purchase_invoice_id', $purchaseInvoiceId)
            ->first();
    }

    /**
     * Escribe —o reactiva— la fila con la que un anticipo o una nota abona una
     * factura. La tabla es única por `(factura, origen)`, así que una segunda
     * aplicación del mismo documento a la misma factura reescribe su fila en vez
     * de agregar otra.
     */
    public function writeApplication(
        WriteSupplierPaymentApplicationCommand $command,
    ): SupplierPaymentApplication {
        $attributes = [
            'company_id' => $command->companyId,
            'applied_amount' => $command->appliedAmount,
            'applied_at' => now(),
            'exchange_rate' => $command->exchangeRate,
            'exchange_difference' => $command->exchangeDifference,
            'status' => 'active',
        ];

        $existing = $this->findApplication(
            $command->sourceType,
            $command->sourceId,
            $command->purchaseInvoiceId,
        );

        if ($existing !== null) {
            $existing->update($attributes);

            return $existing;
        }

        return SupplierPaymentApplication::create([
            ...$attributes,
            'purchase_invoice_id' => $command->purchaseInvoiceId,
            'source_type' => $command->sourceType,
            'source_id' => $command->sourceId,
            'created_by' => $command->createdBy,
        ]);
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
        $source = $payment->applicationSource();
        $sourceId = $payment->applicationSourceId();

        $existing = SupplierPaymentApplication::query()
            ->where('source_type', $source)
            ->where('source_id', $sourceId)
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
                'source_type' => $source,
                'source_id' => $sourceId,
                'exchange_difference' => 0,
                'created_by' => $createdBy,
            ])->id;
        }

        SupplierPaymentApplication::query()
            ->where('source_type', $source)
            ->where('source_id', $sourceId)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'reversed']);
    }

    /**
     * De qué crédito sale el pago. Solo tiene sentido pagando con un anticipo o
     * con una nota; con dinero de por medio se limpia.
     */
    private function creditSource(string $paymentMethod, ?string $creditSourceId): ?string
    {
        return isset(SupplierPayment::CREDIT_METHODS[$paymentMethod]) ? $creditSourceId : null;
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
