<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Commands;

/**
 * Una fila de `app_adjustment_lines` tal como llega desde la pantalla del
 * ajuste.
 *
 * De la pantalla solo viaja **lo contado**: la existencia del sistema, la
 * diferencia, la cantidad en unidad base, la dirección del movimiento y el
 * costo los resuelven el repositorio y `AdjustmentStockService`. Un ajuste que
 * dejara al cliente declarar contra qué está comparando no probaría nada.
 *
 * `unitCost` es la excepción y solo en una revaluación: ahí el usuario define
 * el costo nuevo, que es justamente lo que el documento cambia.
 *
 * La trazabilidad no cabe en la línea: un mismo artículo se cuenta repartido en
 * varios lotes y con varias unidades identificadas, así que viaja en sus
 * propias colecciones. Cuando la línea trae lotes, lo contado en ellos suma
 * exactamente lo contado en la línea.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class AdjustmentLineData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $itemId,
        public readonly string $measurementUnitId,
        public readonly float $countedQuantity,
        /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
        public readonly ?string $locationId = null,
        /** @var array<int, AdjustmentLineLotData> */
        public readonly array $lots = [],
        /** @var array<int, AdjustmentLineSerialData> */
        public readonly array $serials = [],
        /** Costo nuevo por unidad base. Solo lo lee una revaluación. */
        public readonly ?float $unitCost = null,
        public readonly ?string $reason = null,
        public readonly ?string $countedBy = null,
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
            countedQuantity: round((float) ($row['counted_quantity'] ?? 0), 4),
            locationId: $row['location_id'] ?? null,
            lots: AdjustmentLineLotData::collection($row['lots'] ?? []),
            serials: AdjustmentLineSerialData::collection($row['serials'] ?? []),
            unitCost: isset($row['unit_cost']) && $row['unit_cost'] !== ''
                ? round((float) $row['unit_cost'], 6)
                : null,
            reason: $row['reason'] ?? null,
            countedBy: $row['counted_by'] ?? null,
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /** Cuánto de lo contado se repartió en lotes. */
    public function lotQuantity(): float
    {
        return round(array_sum(array_map(
            static fn (AdjustmentLineLotData $lot): float => $lot->countedQuantity,
            $this->activeLots(),
        )), 4);
    }

    /**
     * Los lotes activos de la línea.
     *
     * @return array<int, AdjustmentLineLotData>
     */
    public function activeLots(): array
    {
        return array_values(array_filter(
            $this->lots,
            static fn (AdjustmentLineLotData $lot): bool => $lot->status === 'active',
        ));
    }

    /**
     * Las series activas de la línea.
     *
     * @return array<int, AdjustmentLineSerialData>
     */
    public function activeSerials(): array
    {
        return array_values(array_filter(
            $this->serials,
            static fn (AdjustmentLineSerialData $serial): bool => $serial->status === 'active',
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
