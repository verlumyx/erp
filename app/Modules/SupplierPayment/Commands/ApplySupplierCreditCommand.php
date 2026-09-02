<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Commands;

/**
 * Lo que un anticipo o una nota de crédito quiere abonarle a una factura de
 * compra.
 *
 * El monto es siempre positivo: aplicar y revertir son dos llamadas distintas,
 * no un signo.
 */
class ApplySupplierCreditCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $purchaseInvoiceId,
        public readonly string $sourceType,
        public readonly string $sourceId,
        public readonly float $appliedAmount,
        public readonly float $exchangeRate = 1,
        public readonly ?string $createdBy = null,
    ) {}
}
