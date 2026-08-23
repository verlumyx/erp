<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Commands;

/**
 * Una afectación de saldo, en unidad base.
 *
 * No la construye un Request: la arma el documento que mueve el inventario
 * (entrada, despacho, traslado, ajuste) dentro de su propia transacción. Los
 * tres deltas son incrementos con signo, no valores finales: `-3` descarga
 * tres unidades, `+3` las carga.
 */
class ApplyItemStockMovementCommand
{
    public function __construct(
        public readonly string $companyId,
        public readonly string $itemId,
        public readonly string $warehouseId,
        public readonly string $locationId,
        public readonly float $quantityDelta = 0,
        public readonly float $reservedDelta = 0,
        public readonly float $incomingDelta = 0,
        public readonly ?string $lotId = null,
        /** Costo unitario de la entrada; recalcula el promedio ponderado. */
        public readonly ?float $unitCost = null,
        public readonly ?string $movementAt = null,
    ) {}
}
