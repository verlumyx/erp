<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Commands;

/**
 * Lo facturado de la línea ya resuelto. Lo escribe el único servicio que puede
 * moverlo y no toca ni lo pedido, ni lo recibido, ni los importes: facturar no
 * cambia lo que se pidió, solo cuánto de ello ya vino en una factura.
 */
class WritePurchaseOrderLineInvoicedCommand
{
    public function __construct(
        public readonly float $invoicedQuantity,
    ) {}
}
