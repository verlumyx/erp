<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Commands;

/**
 * Los saldos del proveedor ya resueltos. Los escribe el único servicio que
 * puede moverlos; el resto de su ficha no se toca.
 */
class WriteSupplierBalancesCommand
{
    public function __construct(
        public readonly float $currentBalance,
        public readonly float $advanceBalance,
    ) {}
}
