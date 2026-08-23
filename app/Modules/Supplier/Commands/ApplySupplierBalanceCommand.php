<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Commands;

/**
 * Una afectación a los saldos del proveedor, con signo.
 *
 * `currentBalanceDelta` mueve lo que se le debe: negativo cuando un pago
 * cancela deuda, positivo cuando ese pago se revierte.
 * `advanceBalanceDelta` mueve su crédito a favor.
 */
class ApplySupplierBalanceCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $supplierId,
        public readonly float $currentBalanceDelta = 0,
        public readonly float $advanceBalanceDelta = 0,
    ) {}
}
