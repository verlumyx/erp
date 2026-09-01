<?php

declare(strict_types=1);

namespace App\Modules\Item\Commands;

/**
 * La orden de recalcular el costo promedio de un artículo.
 *
 * No la construye un Request: el promedio no se captura. La arma el documento
 * que acaba de mover existencia —hoy la Entrada— cuando el saldo del que sale
 * ese promedio ya está escrito.
 */
class ApplyItemAverageCostCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $itemId,
    ) {}
}
