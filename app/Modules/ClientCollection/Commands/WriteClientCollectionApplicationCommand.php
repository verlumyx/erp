<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Commands;

/**
 * Una fila del reparto vista desde un origen que no es un cobro: un anticipo o
 * una nota de crédito abonando una factura.
 *
 * El cobro escribe las suyas al capturarse y las confirma después; estas nacen
 * ya aplicadas, porque el crédito que las respalda existe desde antes.
 */
class WriteClientCollectionApplicationCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $salesInvoiceId,
        public readonly string $sourceType,
        public readonly string $sourceId,
        public readonly float $appliedAmount,
        public readonly float $exchangeRate,
        public readonly float $exchangeDifference = 0,
        public readonly ?string $createdBy = null,
    ) {}
}
