<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Commands;

/**
 * Alta de una serie.
 *
 * No se arma desde un Request: la serie nace en el documento que recibe la
 * mercancía (Entrada, Factura de compra), que la captura desde su línea. Ver
 * `ItemSerialCreateService` para las invariantes.
 */
class CreateItemSerialCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $itemId,
        public readonly string $serialNumber,
        public readonly string $createdBy,
        public readonly ?string $lotId = null,
        public readonly ?string $warehouseId = null,
        public readonly string $status = 'available',
    ) {}

}
