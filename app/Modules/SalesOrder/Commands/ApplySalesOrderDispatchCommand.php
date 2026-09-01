<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Commands;

/**
 * Una afectación a lo despachado de una línea de pedido, con signo: positiva
 * cuando el despacho saca la mercancía, negativa cuando ese despacho se anula
 * o cuando el cliente no se queda con lo que le llevaron.
 */
class ApplySalesOrderDispatchCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $salesOrderLineId,
        public readonly float $dispatchedDelta,
    ) {}
}
