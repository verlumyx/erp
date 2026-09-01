<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Commands;

/**
 * Una afectación al saldo de la factura, con signo: positiva cuando un cobro,
 * un anticipo o una nota de crédito la abona, negativa cuando esa aplicación
 * se revierte.
 */
class ApplySalesInvoiceCollectionCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $salesInvoiceId,
        public readonly float $appliedDelta,
    ) {}
}
