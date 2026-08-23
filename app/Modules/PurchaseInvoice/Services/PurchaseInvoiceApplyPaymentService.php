<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Commands\ApplyPurchaseInvoicePaymentCommand;
use App\Modules\PurchaseInvoice\Commands\WritePurchaseInvoicePaymentCommand;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceNotFoundException;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceOverpaidException;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `paid_amount`, `balance` y `payment_status` de una
 * factura de compra.
 *
 * Lo llaman los pagos, los anticipos y las notas de crédito cuando abonan la
 * factura, y otra vez con el signo contrario cuando esa aplicación se revierte.
 * La factura no se recalcula: lo aplicado no puede pasarse de su total ni
 * bajar de cero.
 *
 * La transacción es anidable: llamado desde el documento que aplica se suma a
 * la transacción abierta como savepoint.
 */
class PurchaseInvoiceApplyPaymentService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
    ) {}

    public function execute(ApplyPurchaseInvoicePaymentCommand $command): PurchaseInvoice
    {
        return DB::transaction(function () use ($command): PurchaseInvoice {
            $invoice = $this->repository->lockById($command->purchaseInvoiceId, $command->companyId);

            if ($invoice === null) {
                throw new PurchaseInvoiceNotFoundException;
            }

            $paid = round((float) $invoice->paid_amount + $command->appliedDelta, 2);
            $total = round((float) $invoice->total, 2);

            if ($paid < 0 || $paid > $total) {
                throw new PurchaseInvoiceOverpaidException;
            }

            return $this->repository->writePayment($invoice, new WritePurchaseInvoicePaymentCommand(
                paidAmount: $paid,
                balance: round($total - $paid, 2),
                paymentStatus: $this->paymentStatus($invoice, $paid, $total),
            ));
        });
    }

    /**
     * Saldada es `paid`; con algo abonado, `partial`; sin nada, `pending`. Una
     * factura vencida que sigue debiendo conserva su `overdue`: el día en que
     * venció no lo borra un abono parcial.
     */
    private function paymentStatus(PurchaseInvoice $invoice, float $paid, float $total): string
    {
        if ($paid >= $total && $total > 0) {
            return 'paid';
        }

        /** Vence al final de su día: una factura que vence hoy todavía no está vencida. */
        if ($invoice->due_date !== null && $invoice->due_date->startOfDay()->lt(now()->startOfDay())) {
            return 'overdue';
        }

        return $paid > 0 ? 'partial' : 'pending';
    }
}
