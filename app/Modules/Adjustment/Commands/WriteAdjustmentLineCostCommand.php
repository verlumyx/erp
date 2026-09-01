<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Commands;

/**
 * Cómo quedó de verdad una línea después de tocar el kardex: con qué costo se
 * valoró, cuánto valor movió y en qué dirección.
 *
 * Lo escribe únicamente `AdjustmentPostingService`, al confirmar. Hasta ese
 * momento la línea lleva el promedio que había cuando se capturó, que es una
 * estimación para que la pantalla enseñe el impacto antes de aplicarlo.
 */
class WriteAdjustmentLineCostCommand
{
    public function __construct(
        public readonly float $unitCost,
        public readonly float $totalCost,
        public readonly string $movementType,
    ) {}
}
