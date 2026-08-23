<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Commands;

/**
 * Lo que una aplicación estrena al confirmarse el pago: el momento en que
 * abonó, la tasa con la que se abonó y el diferencial cambiario contra la tasa
 * que congeló la factura.
 */
class PostSupplierPaymentApplicationCommand
{
    public function __construct(
        public readonly string $appliedAt,
        public readonly float $exchangeRate,
        public readonly float $exchangeDifference,
    ) {}
}
