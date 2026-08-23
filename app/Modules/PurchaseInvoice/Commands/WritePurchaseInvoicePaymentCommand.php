<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Commands;

/**
 * El saldo de la factura ya resuelto. Lo escribe el único servicio que puede
 * moverlo, y nunca toca los importes del documento: la deuda vale lo que
 * valía, lo que cambia es cuánto queda por pagar.
 */
class WritePurchaseInvoicePaymentCommand
{
    public function __construct(
        public readonly float $paidAmount,
        public readonly float $balance,
        public readonly string $paymentStatus,
    ) {}
}
