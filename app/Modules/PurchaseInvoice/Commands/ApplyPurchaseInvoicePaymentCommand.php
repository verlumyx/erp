<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Commands;

/**
 * Una afectación al saldo de la factura, con signo: positiva cuando un pago,
 * un anticipo o una nota de crédito la abona, negativa cuando esa aplicación
 * se revierte.
 */
class ApplyPurchaseInvoicePaymentCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $purchaseInvoiceId,
        public readonly float $appliedDelta,
    ) {}
}
