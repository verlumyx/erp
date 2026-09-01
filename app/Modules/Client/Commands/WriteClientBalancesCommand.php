<?php

declare(strict_types=1);

namespace App\Modules\Client\Commands;

/**
 * Los saldos del cliente ya resueltos. Los escribe el único servicio que puede
 * moverlos; el resto de su ficha no se toca.
 */
class WriteClientBalancesCommand
{
    public function __construct(
        public readonly float $currentBalance,
        public readonly float $advanceBalance,
    ) {}
}
