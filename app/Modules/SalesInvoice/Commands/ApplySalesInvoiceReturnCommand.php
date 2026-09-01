<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Commands;

/**
 * Una afectación a lo devuelto de una línea de factura, con signo: positiva
 * cuando una devolución de venta reingresa la mercancía, negativa cuando esa
 * devolución se anula y el cupo vuelve a estar libre.
 */
class ApplySalesInvoiceReturnCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $salesInvoiceLineId,
        public readonly float $returnedDelta,
    ) {}
}
