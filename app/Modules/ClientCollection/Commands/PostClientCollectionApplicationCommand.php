<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Commands;

/**
 * Lo que una aplicación estrena al confirmarse el cobro: el momento en que
 * abonó, la tasa con la que abonó y el diferencial cambiario contra la tasa
 * que congeló la factura.
 */
class PostClientCollectionApplicationCommand
{
    public function __construct(
        public readonly string $appliedAt,
        public readonly float $exchangeRate,
        public readonly float $exchangeDifference,
    ) {}
}
