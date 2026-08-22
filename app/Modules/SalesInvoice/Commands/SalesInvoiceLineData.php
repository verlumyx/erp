<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Commands;

/**
 * Una fila de `app_sales_invoice_lines` tal como llega desde la pantalla.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`) no viajan en el payload: se
 * calculan en el repositorio a partir de cantidad, precio y porcentajes. El
 * costo tampoco: se congela al confirmar con el costo vigente del artículo.
 */
class SalesInvoiceLineData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $itemId,
        public readonly string $measurementUnitId,
        public readonly string $quantity,
        public readonly string $unitPrice,
        public readonly string $discountPercent = '0',
        public readonly ?string $taxId = null,
        public readonly string $taxPercent = '0',
        public readonly string $withholdingPercent = '0',
        public readonly ?string $warehouseId = null,
        public readonly ?string $lotId = null,
        public readonly ?string $serialId = null,
        /** Línea del documento origen: alias del morph map + id. */
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        public readonly ?string $notes = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            itemId: (string) $row['item_id'],
            measurementUnitId: (string) $row['measurement_unit_id'],
            quantity: (string) ($row['quantity'] ?? 0),
            unitPrice: (string) ($row['unit_price'] ?? 0),
            discountPercent: (string) ($row['discount_percent'] ?? 0),
            taxId: $row['tax_id'] ?? null,
            taxPercent: (string) ($row['tax_percent'] ?? 0),
            withholdingPercent: (string) ($row['withholding_percent'] ?? 0),
            warehouseId: $row['warehouse_id'] ?? null,
            lotId: $row['lot_id'] ?? null,
            serialId: $row['serial_id'] ?? null,
            sourceableType: $row['sourceable_type'] ?? null,
            sourceableId: $row['sourceable_id'] ?? null,
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, self>
     */
    public static function collection(array $rows): array
    {
        return array_map(static fn (array $row): self => self::fromArray($row), array_values($rows));
    }
}
