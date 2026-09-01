<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Commands;

/**
 * El costo con el que la mercancía salió de verdad del origen, ya resuelto por
 * el kardex, junto con lo que salió. Es el costo que viaja: el destino la
 * recibe con él, no con el suyo.
 *
 * Lo escribe el único servicio que puede moverlo, y no toca nada más de la
 * línea.
 */
class WriteTransferLineCostCommand
{
    public function __construct(
        public readonly float $unitCost,
        public readonly float $sentQuantity,
    ) {}
}
