<?php

declare(strict_types=1);

namespace App\Modules\Entry\Commands;

/**
 * Una fila de `app_entry_lines` tal como llega desde la pantalla de la entrada,
 * con sus importes ya resueltos.
 *
 * Los montos se calculan aquí y no se leen del request: el cliente envía
 * cantidad, costo unitario y porcentajes, y el backend es el único que decide
 * cuánto vale lo recibido. Lo mismo con la cantidad aceptada: se captura lo que
 * llegó (`quantity`) y lo que se rechazó en inspección (`rejected_quantity`), y
 * lo aceptado es la resta —no un tercer número que pueda contradecir a los
 * otros dos—.
 *
 * `base_quantity`, `unit_cost` y `landed_cost` son la excepción: dependen de
 * las unidades del artículo y de los gastos de la cabecera, así que los
 * resuelven el repositorio y `EntryLandedCostService`.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class EntryLineData
{
    /**
     * @param  array<int, string>  $serialNumbers  Series recibidas; al confirmar
     *                                             se crean en `app_item_serials`.
     */
    public function __construct(
        public readonly ?string $id,
        public readonly string $itemId,
        public readonly string $measurementUnitId,
        public readonly float $quantity,
        public readonly float $rejectedQuantity,
        public readonly float $receivedQuantity,
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
        /** Alias de la línea origen en el morph map (`purchase_order_line`). */
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
        public readonly ?string $locationId = null,
        /** Lote del proveedor. Sin `lotId`, al confirmar se crea con este número. */
        public readonly ?string $lotNumber = null,
        public readonly ?string $lotId = null,
        public readonly ?string $expiresAt = null,
        /** @var array<int, string> */
        public readonly array $serialNumbers = [],
        public readonly ?string $rejectionReason = null,
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

        /** Lo rechazado no puede pasarse de lo que llegó. */
        $rejected = min(round((float) ($row['rejected_quantity'] ?? 0), 4), round($quantity, 4));

        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            itemId: (string) $row['item_id'],
            measurementUnitId: (string) $row['measurement_unit_id'],
            quantity: $quantity,
            rejectedQuantity: max($rejected, 0.0),
            receivedQuantity: round($quantity - max($rejected, 0.0), 4),
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
            locationId: $row['location_id'] ?? null,
            lotNumber: $row['lot_number'] ?? null,
            lotId: $row['lot_id'] ?? null,
            expiresAt: $row['expires_at'] ?? null,
            serialNumbers: self::serials($row['serial_numbers'] ?? []),
            rejectionReason: $row['rejection_reason'] ?? null,
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * Series de la línea, limpias de vacíos y de repetidas: una serie identifica
     * una unidad, así que no puede llegar dos veces en la misma línea.
     *
     * @return array<int, string>
     */
    private static function serials(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $serials = array_filter(
            array_map(static fn (mixed $serial): string => trim((string) $serial), $value),
            static fn (string $serial): bool => $serial !== '',
        );

        return array_values(array_unique($serials));
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
