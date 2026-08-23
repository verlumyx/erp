<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Commands;

/**
 * La anulación de un movimiento ya registrado.
 *
 * No lleva cantidad ni bodega: la contrapartida copia todo del original y solo
 * invierte el tipo. Lo único propio es cuándo se anula y por qué.
 */
class ReverseInventoryMovementCommand
{
    public function __construct(
        public readonly string $movementId,
        public readonly ?string $movementDate = null,
        public readonly ?string $notes = null,
        public readonly ?string $createdBy = null,
    ) {}
}
