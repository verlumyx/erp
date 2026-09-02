<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;

/**
 * El día que pasa y deja una factura vencida.
 *
 * `payment_status` cambia solo cuando algo toca la factura —un cobro, una nota
 * de crédito—, y una factura que vence sin que nadie la toque se quedaría en
 * `pending` para siempre. Este servicio es el que hace ese trabajo, y lo llama
 * el comando diario `invoices:mark-overdue`.
 *
 * Una factura vence **al terminar su día**: la que vence hoy todavía no está
 * vencida, igual que en `SalesInvoiceApplyCollectionService`.
 */
class SalesInvoiceMarkOverdueService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
    ) {}

    /** @return int Cuántas facturas pasaron a `overdue`. */
    public function execute(?string $onDate = null, ?string $companyId = null): int
    {
        return $this->repository->markOverdue($onDate ?? now()->toDateString(), $companyId);
    }
}
