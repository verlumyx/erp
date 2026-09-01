<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

/**
 * El costo con el que la línea salió de verdad, ya resuelto por el kardex. Lo
 * escribe el único servicio que puede moverlo, y no toca nada más de la línea:
 * el precio, la cantidad y los impuestos valen lo que valían.
 */
class WriteDispatchLineCostCommand
{
    public function __construct(
        public readonly float $unitCost,
    ) {}
}
