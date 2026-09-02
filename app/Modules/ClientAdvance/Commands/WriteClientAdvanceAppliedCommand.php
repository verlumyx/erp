<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Commands;

/**
 * Lo que el anticipo ya se llevó y lo que le queda disponible, ya resuelto. Lo
 * escribe el único servicio que puede moverlo y no toca el monto recibido:
 * gastar el anticipo no cambia lo que entró.
 */
class WriteClientAdvanceAppliedCommand
{
    public function __construct(
        public readonly float $appliedAmount,
        public readonly float $balance,
    ) {}
}
