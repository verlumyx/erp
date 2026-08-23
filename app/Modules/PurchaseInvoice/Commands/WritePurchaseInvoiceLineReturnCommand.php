<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Commands;

/**
 * Lo devuelto de la línea ya resuelto. Lo escribe el único servicio que puede
 * moverlo, y no toca ni la cantidad facturada ni los importes: lo facturado
 * vale lo que valía, lo que cambia es cuánto de ello volvió al proveedor.
 */
class WritePurchaseInvoiceLineReturnCommand
{
    public function __construct(
        public readonly float $returnedQuantity,
    ) {}
}
