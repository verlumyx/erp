<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Commands;

/**
 * La parte calculada del asiento, lista para persistirse.
 *
 * La arma `InventoryMovementRegisterService` con el saldo que dejó la
 * afectación de existencia: la aritmética vive en el servicio y el repositorio
 * solo escribe el resultado, igual que en Existencias.
 */
class WriteInventoryMovementCommand
{
    public function __construct(
        public readonly float $unitCost,
        public readonly float $totalCost,
        public readonly float $balanceQuantity,
        public readonly float $balanceCost,
        public readonly float $balanceValue,
    ) {}
}
