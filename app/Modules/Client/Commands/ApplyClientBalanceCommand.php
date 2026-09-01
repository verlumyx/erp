<?php

declare(strict_types=1);

namespace App\Modules\Client\Commands;

/**
 * Una afectación a los saldos del cliente, con signo.
 *
 * `currentBalanceDelta` mueve lo que nos debe: negativo cuando un cobro cancela
 * deuda, positivo cuando ese cobro se revierte. `advanceBalanceDelta` mueve su
 * crédito a favor.
 */
class ApplyClientBalanceCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $clientId,
        public readonly float $currentBalanceDelta = 0,
        public readonly float $advanceBalanceDelta = 0,
    ) {}
}
