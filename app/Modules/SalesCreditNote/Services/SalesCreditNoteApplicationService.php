<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\ClientCollection\Commands\ApplyClientCreditCommand;
use App\Modules\ClientCollection\Services\ClientCollectionApplyCreditService;
use App\Modules\SalesCreditNote\Commands\UpdateStatusSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\WriteSalesCreditNoteAppliedCommand;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El crédito de la nota gastándose en la factura que corrige.
 *
 * Bajar el `current_balance` del cliente no basta: mientras la `FVE` siga
 * marcando su `balance` entero, el saldo del cliente y la suma de sus facturas
 * abiertas dejan de cuadrar. Aquí es donde la nota abona la factura de verdad,
 * escribiendo su fila en `app_client_collection_applications` con
 * `source_type = 'credit_note'`.
 *
 * Se aplica solo desde `confirmed` y solo a la factura que la nota señala: una
 * nota suelta —sin `sales_invoice_id`— queda como crédito disponible del
 * cliente y se gastará desde un cobro.
 *
 * Lo que se abona es lo menor entre el crédito que le queda a la nota y lo que
 * la factura todavía debe: una nota mayor que el saldo vivo de la factura no la
 * sobrepaga, guarda el resto como disponible.
 */
class SalesCreditNoteApplicationService
{
    public function __construct(
        private readonly SalesCreditNoteRepositoryInterface $repository,
        private readonly SalesInvoiceRepositoryInterface $salesInvoices,
        private readonly ClientCollectionApplyCreditService $credits,
    ) {}

    /** Abona la factura que la nota corrige, si señala alguna y algo debe. */
    public function apply(SalesCreditNote $note): void
    {
        if (blank($note->sales_invoice_id)) {
            return;
        }

        DB::transaction(function () use ($note): void {
            $invoice = $this->salesInvoices->findById((string) $note->sales_invoice_id, $note->company_id);

            if (! $invoice instanceof SalesInvoice) {
                return;
            }

            $available = round((float) $note->total - (float) $note->applied_amount, 2);
            $amount = min($available, round((float) $invoice->balance, 2));

            if ($amount <= 0) {
                return;
            }

            $this->credits->apply(new ApplyClientCreditCommand(
                companyId: $note->company_id,
                salesInvoiceId: $invoice->id,
                sourceType: SalesCreditNote::APPLICATION_SOURCE,
                sourceId: $note->id,
                appliedAmount: $amount,
                exchangeRate: (float) $note->exchange_rate,
                createdBy: $note->created_by,
            ));

            $this->writeApplied($note, round((float) $note->applied_amount + $amount, 2));
        });
    }

    /**
     * Deshace lo que la nota abonó: las facturas recuperan su saldo, las filas
     * quedan en `reversed` y la nota vuelve a tener su crédito entero.
     */
    public function revert(SalesCreditNote $note): void
    {
        DB::transaction(function () use ($note): void {
            $reverted = $this->credits->revert(
                $note->company_id,
                SalesCreditNote::APPLICATION_SOURCE,
                $note->id,
            );

            if ($reverted <= 0.0) {
                return;
            }

            $this->writeApplied($note, max(round((float) $note->applied_amount - $reverted, 2), 0));
        });
    }

    /**
     * Deja escrito lo aplicado y lo disponible, y agota la nota cuando ya no le
     * queda crédito: una nota totalmente aplicada está `completed`.
     */
    private function writeApplied(SalesCreditNote $note, float $applied): void
    {
        $total = round((float) $note->total, 2);
        $balance = round($total - $applied, 2);

        $this->repository->writeApplied($note, new WriteSalesCreditNoteAppliedCommand(
            appliedAmount: $applied,
            balance: $balance,
        ));

        if ($balance <= 0 && $note->status === 'confirmed') {
            $this->repository->updateStatus($note, new UpdateStatusSalesCreditNoteCommand('completed'));
        }
    }
}
