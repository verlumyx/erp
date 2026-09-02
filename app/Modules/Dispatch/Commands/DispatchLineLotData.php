<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

/**
 * Uno de los lotes de los que sale una línea del despacho.
 *
 * Aquí el lote sí es un id: el despacho saca mercancía que ya existe, así que
 * el lote se elige del maestro y no puede nacer con el documento.
 */
class DispatchLineLotData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $lotId,
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
            lotId: (string) ($row['lot_id'] ?? ''),
            quantity: round((float) ($row['quantity'] ?? 0), 4),
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
