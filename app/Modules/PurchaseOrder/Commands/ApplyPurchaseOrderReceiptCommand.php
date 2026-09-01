<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Commands;

/**
 * Una afectación a lo recibido de una línea de orden de compra, con signo:
 * positiva cuando una entrada mete la mercancía en la bodega, negativa cuando
 * esa entrada se anula y el cupo vuelve a estar pendiente.
 */
class ApplyPurchaseOrderReceiptCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $purchaseOrderLineId,
        public readonly float $receivedDelta,
    ) {}
}
