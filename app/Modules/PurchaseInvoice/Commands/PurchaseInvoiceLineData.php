<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Commands;

/**
 * Una fila de `app_purchase_invoice_lines` tal como llega desde la pantalla de
 * la factura, con sus importes ya resueltos.
 *
 * Los montos se calculan aquí y no se leen del request: el cliente envía
 * cantidad, costo y porcentajes, y el backend es el único que decide cuánto
 * suma la línea. `base_quantity`, `line_number` y `landed_cost` son la
 * excepción: dependen del artículo o de los cargos de la cabecera, así que los
 * resuelve el repositorio.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class PurchaseInvoiceLineData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $itemId,
        public readonly string $measurementUnitId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $discountPercent,
        public readonly float $discountAmount,
        public readonly float $taxPercent,
        public readonly float $taxAmount,
        public readonly float $withholdingPercent,
        public readonly float $withholdingAmount,
        public readonly float $subtotal,
        public readonly float $total,
        public readonly ?string $taxId = null,
        /** Línea de la orden que esta línea factura, con su alias del morph map. */
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        /** Bodega propia de la línea; sin ella manda la de la cabecera. */
        public readonly ?string $warehouseId = null,
        public readonly ?string $lotId = null,
        public readonly ?string $notes = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $quantity = (float) ($row['quantity'] ?? 0);
        $unitPrice = (float) ($row['unit_price'] ?? 0);
        $gross = $quantity * $unitPrice;

        /** El porcentaje manda: si viene un descuento en %, el monto se deriva de él. */
        $discountPercent = (float) ($row['discount_percent'] ?? 0);
        $discountAmount = $discountPercent > 0
            ? round($gross * $discountPercent / 100, 2)
            : round((float) ($row['discount_amount'] ?? 0), 2);

        $subtotal = round($gross - $discountAmount, 2);

        $taxPercent = (float) ($row['tax_percent'] ?? 0);
        $taxAmount = round($subtotal * $taxPercent / 100, 2);

        /** La retención se practica sobre el impuesto, no sobre la base imponible. */
        $withholdingPercent = (float) ($row['withholding_percent'] ?? 0);
        $withholdingAmount = round($taxAmount * $withholdingPercent / 100, 2);

        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            itemId: (string) $row['item_id'],
            measurementUnitId: (string) $row['measurement_unit_id'],
            quantity: $quantity,
            unitPrice: $unitPrice,
            discountPercent: $discountPercent,
            discountAmount: $discountAmount,
            taxPercent: $taxPercent,
            taxAmount: $taxAmount,
            withholdingPercent: $withholdingPercent,
            withholdingAmount: $withholdingAmount,
            subtotal: $subtotal,
            total: round($subtotal + $taxAmount, 2),
            taxId: $row['tax_id'] ?? null,
            sourceableType: $row['sourceable_type'] ?? null,
            sourceableId: $row['sourceable_id'] ?? null,
            warehouseId: $row['warehouse_id'] ?? null,
            lotId: $row['lot_id'] ?? null,
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
