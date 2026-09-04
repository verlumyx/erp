<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Cerrar la factura no es una decisión: es no deber nada.
 *
 * `completed` significa saldada, y quien lo sabe es `payment_status`, que ya
 * resuelve `PurchaseInvoiceApplyPaymentService` cada vez que un pago, un
 * anticipo o una nota de crédito la abona. Este servicio solo traslada ese
 * hecho al estado del documento, y por eso se llama justo después.
 *
 * Lee de cero cada vez: revertir el pago que la había saldado devuelve la
 * factura a `confirmed`, porque vuelve a deber.
 *
 * `draft` y `cancelled` quedan fuera: una factura sin confirmar todavía no debe
 * nada y una anulada ya no debe.
 */
class PurchaseInvoiceSettleStatusService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
    ) {}

    public function execute(string $invoiceId, ?string $companyId = null): void
    {
        DB::transaction(function () use ($invoiceId, $companyId): void {
            $invoice = $this->repository->lockById($invoiceId, $companyId);

            if ($invoice === null || ! in_array($invoice->status, PurchaseInvoice::PAYABLE_STATUSES, true)) {
                return;
            }

            $status = $invoice->payment_status === 'paid' ? 'completed' : 'confirmed';

            if ($status === $invoice->status) {
                return;
            }

            $this->repository->writeSettledStatus($invoice, $status);
        });
    }
}
