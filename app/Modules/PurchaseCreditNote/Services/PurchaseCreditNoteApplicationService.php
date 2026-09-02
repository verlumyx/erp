<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\PurchaseCreditNote\Commands\UpdateStatusPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\WritePurchaseCreditNoteAppliedCommand;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\SupplierPayment\Commands\ApplySupplierCreditCommand;
use App\Modules\SupplierPayment\Services\SupplierPaymentApplyCreditService;
use Illuminate\Support\Facades\DB;

/**
 * El crédito de la nota gastándose en la factura que corrige.
 *
 * Bajar el `current_balance` del proveedor no basta: mientras la `FCO` siga
 * marcando su `balance` entero, el saldo del proveedor y la suma de sus
 * facturas abiertas dejan de cuadrar. Aquí es donde la nota abona la factura de
 * verdad, escribiendo su fila en `app_supplier_payment_applications` con
 * `source_type = 'credit_note'`.
 *
 * Se aplica solo desde `confirmed` y solo a la factura que la nota señala: una
 * nota suelta —sin `purchase_invoice_id`— queda como crédito disponible con el
 * proveedor y se gastará desde un pago.
 */
class PurchaseCreditNoteApplicationService
{
    public function __construct(
        private readonly PurchaseCreditNoteRepositoryInterface $repository,
        private readonly PurchaseInvoiceRepositoryInterface $purchaseInvoices,
        private readonly SupplierPaymentApplyCreditService $credits,
    ) {}

    /** Abona la factura que la nota corrige, si señala alguna y algo debe. */
    public function apply(PurchaseCreditNote $note): void
    {
        if (blank($note->purchase_invoice_id)) {
            return;
        }

        DB::transaction(function () use ($note): void {
            $invoice = $this->purchaseInvoices->findById((string) $note->purchase_invoice_id, $note->company_id);

            if (! $invoice instanceof PurchaseInvoice) {
                return;
            }

            $available = round((float) $note->total - (float) $note->applied_amount, 2);
            $amount = min($available, round((float) $invoice->balance, 2));

            if ($amount <= 0) {
                return;
            }

            $this->credits->apply(new ApplySupplierCreditCommand(
                companyId: $note->company_id,
                purchaseInvoiceId: $invoice->id,
                sourceType: PurchaseCreditNote::APPLICATION_SOURCE,
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
    public function revert(PurchaseCreditNote $note): void
    {
        DB::transaction(function () use ($note): void {
            $reverted = $this->credits->revert(
                $note->company_id,
                PurchaseCreditNote::APPLICATION_SOURCE,
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
    private function writeApplied(PurchaseCreditNote $note, float $applied): void
    {
        $total = round((float) $note->total, 2);
        $balance = round($total - $applied, 2);

        $this->repository->writeApplied($note, new WritePurchaseCreditNoteAppliedCommand(
            appliedAmount: $applied,
            balance: $balance,
        ));

        if ($balance <= 0 && $note->status === 'confirmed') {
            $this->repository->updateStatus($note, new UpdateStatusPurchaseCreditNoteCommand('completed'));
        }
    }
}
