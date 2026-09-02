<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Commands;

/**
 * Una fila del reparto vista desde un origen que no es un pago: un anticipo o
 * una nota de crédito abonando una factura de compra.
 *
 * El pago escribe las suyas al capturarse y las confirma después; estas nacen
 * ya aplicadas, porque el crédito que las respalda existe desde antes.
 */
class WriteSupplierPaymentApplicationCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $purchaseInvoiceId,
        public readonly string $sourceType,
        public readonly string $sourceId,
        public readonly float $appliedAmount,
        public readonly float $exchangeRate,
        public readonly float $exchangeDifference = 0,
        public readonly ?string $createdBy = null,
    ) {}
}
