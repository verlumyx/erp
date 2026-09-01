<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Commands;

/**
 * Una fila de `app_transfer_lines` tal como llega desde la pantalla del
 * traslado.
 *
 * Es deliberadamente más pobre que la línea de un despacho o de una entrada:
 * el traslado no pone precio a nada, así que ni el precio, ni el descuento, ni
 * el impuesto se capturan. Lo único que vale dinero es el costo con el que la
 * mercancía viaja, y ese lo resuelven `TransferCostService` y el kardex.
 *
 * `base_quantity`, `unit_cost` y `line_number` también los ponen el repositorio
 * y los servicios: dependen del artículo, de la existencia o de las líneas ya
 * guardadas.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class TransferLineData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $itemId,
        public readonly string $measurementUnitId,
        public readonly float $quantity,
        /** Vacías dejan que el kardex tome la ubicación por defecto de cada bodega. */
        public readonly ?string $originLocationId = null,
        public readonly ?string $destinationLocationId = null,
        public readonly ?string $lotId = null,
        public readonly ?string $serialId = null,
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
            quantity: round((float) ($row['quantity'] ?? 0), 4),
            originLocationId: $row['origin_location_id'] ?? null,
            destinationLocationId: $row['destination_location_id'] ?? null,
            lotId: $row['lot_id'] ?? null,
            serialId: $row['serial_id'] ?? null,
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
