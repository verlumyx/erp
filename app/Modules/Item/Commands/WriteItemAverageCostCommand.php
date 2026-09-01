<?php

declare(strict_types=1);

namespace App\Modules\Item\Commands;

/**
 * El costo promedio ya resuelto. Lo escribe el único servicio que puede
 * moverlo, y no toca nada más del artículo: el maestro sigue siendo del
 * usuario, este número es del inventario.
 */
class WriteItemAverageCostCommand
{
    public function __construct(
        public readonly float $averageCost,
    ) {}
}
