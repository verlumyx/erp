<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Commands;

/**
 * Una fila de `app_sales_order_lines` tal como llega desde la pantalla del pedido.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 *
 * Los importes (`subtotal`, `tax_amount`, `total`) no viajan en el payload: se
 * calculan en el repositorio a partir de cantidad, precio y porcentajes.
 */
class SalesOrderLineData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $itemId,
        public readonly string $measurementUnitId,
        public readonly string $quantity,
        public readonly string $unitPrice,
        public readonly string $listPrice = '0',
        public readonly string $discountPercent = '0',
        public readonly ?string $taxId = null,
        public readonly string $taxPercent = '0',
        public readonly string $withholdingPercent = '0',
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
            /** Sin precio de lista explícito, el precio pactado es el de lista. */
            listPrice: (string) ($row['list_price'] ?? $row['unit_price'] ?? 0),
            discountPercent: (string) ($row['discount_percent'] ?? 0),
            taxId: $row['tax_id'] ?? null,
            taxPercent: (string) ($row['tax_percent'] ?? 0),
            withholdingPercent: (string) ($row['withholding_percent'] ?? 0),
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
