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
        public readonly ?string $lotId = null,
        public readonly ?string $serialId = null,
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
            lotId: $row['lot_id'] ?? null,
            serialId: $row['serial_id'] ?? null,
            unitCost: isset($row['unit_cost']) && $row['unit_cost'] !== ''
                ? round((float) $row['unit_cost'], 6)
                : null,
            reason: $row['reason'] ?? null,
            countedBy: $row['counted_by'] ?? null,
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
