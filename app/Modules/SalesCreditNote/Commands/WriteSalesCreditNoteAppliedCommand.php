<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Commands;

/**
 * Lo que la nota ya se llevó y lo que le queda disponible, ya resuelto. Lo
 * escribe el único servicio que puede moverlo y no toca ni el total ni las
 * líneas: aplicar el crédito no cambia lo que la nota vale.
 */
class WriteSalesCreditNoteAppliedCommand
{
    public function __construct(
        public readonly float $appliedAmount,
        public readonly float $balance,
    ) {}
}
