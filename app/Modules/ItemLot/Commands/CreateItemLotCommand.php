<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Commands;

/**
 * Alta de un lote.
 *
 * No se arma desde un Request: el lote nace en el documento que recibe la
 * mercancía (Entrada, Factura de compra), que construye este comando con sus
 * propios datos de línea. Ver `ItemLotCreateService` para las invariantes.
 */
class CreateItemLotCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $itemId,
        public readonly string $lotNumber,
        public readonly string $createdBy,
        public readonly ?string $manufacturedAt = null,
        public readonly ?string $expiresAt = null,
        public readonly ?string $supplierId = null,
        public readonly string $status = 'active',
    ) {}

}
