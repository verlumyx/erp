<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Commands;

/**
 * El saldo ya calculado, listo para persistirse.
 *
 * Lo arma `ItemStockApplyMovementService` a partir del saldo bloqueado más el
 * movimiento: la aritmética (promedio ponderado, disponible, valor) vive en el
 * servicio, y el repositorio solo escribe el resultado.
 */
class WriteItemStockBalanceCommand
{
    public function __construct(
        public readonly float $quantity,
        public readonly float $reservedQuantity,
        public readonly float $incomingQuantity,
        public readonly float $availableQuantity,
        public readonly float $averageCost,
        public readonly float $totalValue,
        public readonly ?string $lastMovementAt = null,
    ) {}
}
