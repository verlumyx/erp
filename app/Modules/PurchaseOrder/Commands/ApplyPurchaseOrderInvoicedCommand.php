<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Commands;

/**
 * Una afectación a lo facturado de una línea de orden de compra, con signo:
 * positiva cuando la factura de compra se confirma, negativa cuando esa factura
 * se anula y lo pedido vuelve a estar sin facturar.
 */
class ApplyPurchaseOrderInvoicedCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $purchaseOrderLineId,
        public readonly float $invoicedDelta,
    ) {}
}
