<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Commands;

/**
 * Uno de los lotes que se contaron en una línea del ajuste.
 *
 * De la pantalla solo viaja **lo contado en ese lote**: la existencia con la
 * que se compara, la diferencia y el costo los resuelve el backend, igual que
 * hace con la línea. Un ajuste que dejara al cliente declarar contra qué está
 * comparando no probaría nada.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class AdjustmentLineLotData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $lotId,
        public readonly float $countedQuantity,
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
            lotId: (string) ($row['lot_id'] ?? ''),
            countedQuantity: round((float) ($row['counted_quantity'] ?? 0), 4),
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * Las filas de lote de una línea, sin las que llegaron sin lote: una fila
     * que el usuario abrió y no llenó no es un lote.
     *
     * @return array<int, self>
     */
    public static function collection(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $lots = array_map(
            static fn (array $row): self => self::fromArray($row),
            array_values(array_filter($rows, 'is_array')),
        );

        return array_values(array_filter(
            $lots,
            static fn (self $lot): bool => $lot->lotId !== '',
        ));
    }
}
