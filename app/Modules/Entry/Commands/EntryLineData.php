<?php

declare(strict_types=1);

namespace App\Modules\Entry\Commands;

/**
 * Una fila de `app_entry_lines` tal como llega desde la pantalla de la entrada,
 * con sus importes ya resueltos.
 *
 * La pantalla **no captura dinero**. El costo, el impuesto y el descuento se
 * deciden en la orden de compra, así que llegan aquí en cero y los pone
 * `EntryPricingService` con `withPricing()` —copiándolos de la línea de la
 * orden o, sin orden, del costo promedio del artículo—. Un `unit_price` que
 * mande el cliente se ignora: no es una decisión suya.
 *
 * De la línea sí se captura la cantidad: lo que se recibe (`quantity`) y lo que
 * la inspección rechaza (`rejected_quantity`); lo aceptado es la resta —no un
 * tercer número que pueda contradecir a los otros dos—.
 *
 * `base_quantity`, `unit_cost` y `landed_cost` son la excepción: dependen de
 * las unidades del artículo y de los gastos de la cabecera, así que los
 * resuelven el repositorio y el prorrateo de gastos.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class EntryLineData
{
    /**
     * @param  array<int, EntryLineLotData>  $lots  Los lotes con los que llegó la línea.
     * @param  array<int, EntryLineSerialData>  $serials  Las unidades con serie.
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
        /** @var array<int, EntryLineLotData> */
        public readonly array $lots = [],
        /** @var array<int, EntryLineSerialData> */
        public readonly array $serials = [],
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

        /** Lo rechazado no puede pasarse de lo que llegó. */
        $rejected = max(min(round((float) ($row['rejected_quantity'] ?? 0), 4), round($quantity, 4)), 0.0);

        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            itemId: (string) $row['item_id'],
            measurementUnitId: (string) $row['measurement_unit_id'],
            quantity: $quantity,
            rejectedQuantity: $rejected,
            receivedQuantity: round($quantity - $rejected, 4),
            unitPrice: 0.0,
            discountPercent: 0.0,
            discountAmount: 0.0,
            taxPercent: 0.0,
            taxAmount: 0.0,
            withholdingPercent: 0.0,
            withholdingAmount: 0.0,
            subtotal: 0.0,
            total: 0.0,
            taxId: null,
            sourceableType: $row['sourceable_type'] ?? null,
            sourceableId: $row['sourceable_id'] ?? null,
            locationId: $row['location_id'] ?? null,
            lots: EntryLineLotData::collection($row['lots'] ?? []),
            serials: EntryLineSerialData::collection($row['serials'] ?? []),
            rejectionReason: $row['rejection_reason'] ?? null,
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * La misma línea con el costo y los cargos que le puso el sistema.
     *
     * Los montos se derivan aquí y en ningún otro sitio: el descuento sale del
     * porcentaje, el impuesto del subtotal y la retención del impuesto —no de
     * la base imponible—.
     */
    public function withPricing(
        float $unitPrice,
        ?string $taxId,
        float $taxPercent,
        float $withholdingPercent,
        float $discountPercent,
    ): self {
        $gross = $this->quantity * $unitPrice;

        $discountAmount = round($gross * $discountPercent / 100, 2);
        $subtotal = round($gross - $discountAmount, 2);
        $taxAmount = round($subtotal * $taxPercent / 100, 2);

        return new self(
            id: $this->id,
            itemId: $this->itemId,
            measurementUnitId: $this->measurementUnitId,
            quantity: $this->quantity,
            rejectedQuantity: $this->rejectedQuantity,
            receivedQuantity: $this->receivedQuantity,
            unitPrice: $unitPrice,
            discountPercent: $discountPercent,
            discountAmount: $discountAmount,
            taxPercent: $taxPercent,
            taxAmount: $taxAmount,
            withholdingPercent: $withholdingPercent,
            withholdingAmount: round($taxAmount * $withholdingPercent / 100, 2),
            subtotal: $subtotal,
            total: round($subtotal + $taxAmount, 2),
            taxId: $taxId,
            sourceableType: $this->sourceableType,
            sourceableId: $this->sourceableId,
            locationId: $this->locationId,
            lots: $this->lots,
            serials: $this->serials,
            rejectionReason: $this->rejectionReason,
            notes: $this->notes,
            status: $this->status,
        );
    }

    /**
     * Los lotes activos de la línea. Los desactivados no cuentan ni para el
     * cuadre ni para el kardex.
     *
     * @return array<int, EntryLineLotData>
     */
    public function activeLots(): array
    {
        return array_values(array_filter(
            $this->lots,
            static fn (EntryLineLotData $lot): bool => $lot->status === 'active',
        ));
    }

    /**
     * @return array<int, EntryLineSerialData>
     */
    public function activeSerials(): array
    {
        return array_values(array_filter(
            $this->serials,
            static fn (EntryLineSerialData $serial): bool => $serial->status === 'active',
        ));
    }

    /** Cuánto de la línea se repartió en lotes. */
    public function lotQuantity(): float
    {
        return round(array_sum(array_map(
            static fn (EntryLineLotData $lot): float => $lot->quantity,
            $this->activeLots(),
        )), 4);
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
