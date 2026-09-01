<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Commands;

/**
 * Lo cobrado de la factura ya resuelto. Lo escribe el único servicio que puede
 * moverlo; el resto de la factura no se toca.
 */
class WriteSalesInvoiceCollectionCommand
{
    public function __construct(
        public readonly float $paidAmount,
        public readonly float $balance,
        public readonly string $paymentStatus,
    ) {}
}
