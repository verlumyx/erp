<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Commands;

/**
 * Lo recibido de la línea ya resuelto, con lo que le queda por llegar. Lo
 * escribe el único servicio que puede moverlo, y no toca ni la cantidad pedida
 * ni los importes: lo pedido vale lo que valía, lo que cambia es cuánto de ello
 * ya está en la bodega.
 */
class WritePurchaseOrderLineReceiptCommand
{
    public function __construct(
        public readonly float $receivedQuantity,
        public readonly float $pendingQuantity,
    ) {}
}
