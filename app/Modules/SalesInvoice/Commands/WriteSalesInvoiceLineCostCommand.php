<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Commands;

/**
 * El costo congelado de una línea, ya resuelto contra el kardex: lo que valía
 * la mercancía el día que salió, lo que costó la línea entera y el margen que
 * dejó. No toca ni el precio ni la cantidad: vender no cambia lo facturado.
 */
class WriteSalesInvoiceLineCostCommand
{
    public function __construct(
        public readonly float $unitCost,
        public readonly float $totalCost,
        public readonly float $marginAmount,
    ) {}
}
