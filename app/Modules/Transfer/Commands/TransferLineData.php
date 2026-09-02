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
 * La ubicación, el lote y la serie tampoco se capturan aquí: la bodega de
 * origen y la de destino ya están en la cabecera, y el número de la caja lo lee
 * quien tiene la mercancía delante, al despacharla.
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
