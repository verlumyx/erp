<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Commands;

/**
 * Una afectación a lo devuelto de una línea de factura, con signo: positiva
 * cuando una devolución de compra saca la mercancía, negativa cuando esa
 * devolución se anula y el cupo vuelve a estar libre.
 */
class ApplyPurchaseInvoiceReturnCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $purchaseInvoiceLineId,
        public readonly float $returnedDelta,
    ) {}
}
