<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

/**
 * Una fila de `app_dispatch_lines` tal como llega desde la pantalla del
 * despacho, con sus importes ya resueltos.
 *
 * La pantalla del despacho **no captura dinero**: el precio, el impuesto y el
 * descuento se deciden en la orden de venta, y aquí los pone
 * `DispatchPricingService` antes de construir este DTO —copiándolos de la línea
 * del pedido o, sin pedido, del costo promedio del artículo—. Los montos se
 * derivan de eso. Son informativos —el despacho no factura—, pero la guía tiene
 * que enseñar lo mismo que el pedido.
 *
 * `base_quantity`, `unit_cost` y `line_number` son la excepción: dependen del
 * artículo, de la existencia o de las líneas ya guardadas, así que los
 * resuelven el repositorio y `DispatchCostService`.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class DispatchLineData
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
        /** Alias de la línea origen; hoy solo `sales_order_line`. */
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        /** @var array<int, DispatchLineLotData> */
        public readonly array $lots = [],
        /** @var array<int, DispatchLineSerialData> */
        public readonly array $serials = [],
        /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
        public readonly ?string $locationId = null,
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
            quantity: (float) ($row['quantity'] ?? 0),
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
            lots: DispatchLineLotData::collection($row['lots'] ?? []),
            serials: DispatchLineSerialData::collection($row['serials'] ?? []),
            locationId: $row['location_id'] ?? null,
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * La misma línea con el precio y los cargos que le puso el sistema.
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
            lots: $this->lots,
            serials: $this->serials,
            locationId: $this->locationId,
            notes: $this->notes,
            status: $this->status,
        );
    }

    /** Cuánto de la línea se repartió en lotes. */
    public function lotQuantity(): float
    {
        return round(array_sum(array_map(
            static fn (DispatchLineLotData $lot): float => $lot->quantity,
            $this->activeLots(),
        )), 4);
    }

    /**
     * Los lotes activos de la línea.
     *
     * @return array<int, DispatchLineLotData>
     */
    public function activeLots(): array
    {
        return array_values(array_filter(
            $this->lots,
            static fn (DispatchLineLotData $lot): bool => $lot->status === 'active',
        ));
    }

    /**
     * Las series activas de la línea.
     *
     * @return array<int, DispatchLineSerialData>
     */
    public function activeSerials(): array
    {
        return array_values(array_filter(
            $this->serials,
            static fn (DispatchLineSerialData $serial): bool => $serial->status === 'active',
        ));
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
