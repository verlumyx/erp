<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Cerrar la factura no es una decisión: es que el cliente ya no deba nada.
 *
 * `completed` significa cobrada, y quien lo sabe es `payment_status`, que ya
 * resuelve `SalesInvoiceApplyCollectionService` cada vez que un cobro, un
 * anticipo o una nota de crédito la abona. Este servicio solo traslada ese
 * hecho al estado del documento, y por eso se llama justo después.
 *
 * Lee de cero cada vez: revertir el cobro que la había saldado devuelve la
 * factura a `confirmed`, porque vuelve a ser una cuenta por cobrar.
 *
 * `draft` y `cancelled` quedan fuera: una factura sin emitir todavía no cobra
 * nada y una anulada ya no cobra.
 */
class SalesInvoiceSettleStatusService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
    ) {}

    public function execute(string $invoiceId, ?string $companyId = null): void
    {
        DB::transaction(function () use ($invoiceId, $companyId): void {
            $invoice = $this->repository->lockById($invoiceId, $companyId);

            if ($invoice === null || ! in_array($invoice->status, SalesInvoice::COLLECTIBLE_STATUSES, true)) {
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
