<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;

/**
 * El día que pasa y deja una factura de compra vencida.
 *
 * `payment_status` cambia solo cuando algo toca la factura —un pago, una nota
 * de crédito—, y una factura que vence sin que nadie la toque se quedaría en
 * `pending` para siempre. Este servicio es el que hace ese trabajo, y lo llama
 * el comando diario `invoices:mark-overdue` (`docs/compras.md` §3.2).
 *
 * Una factura vence **al terminar su día**: la que vence hoy todavía no está
 * vencida, igual que en `PurchaseInvoiceApplyPaymentService`.
 */
class PurchaseInvoiceMarkOverdueService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
    ) {}

    /** @return int Cuántas facturas pasaron a `overdue`. */
    public function execute(?string $onDate = null, ?string $companyId = null): int
    {
        return $this->repository->markOverdue($onDate ?? now()->toDateString(), $companyId);
    }
}
