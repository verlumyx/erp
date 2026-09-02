<?php

declare(strict_types=1);

namespace App\Modules\Entry\Commands;

/**
 * Uno de los lotes con los que llega una línea de la entrada.
 *
 * Se captura el número que trae la caja, no un id: el lote puede no existir
 * todavía en el maestro, y hasta que la entrada no se confirma no hay mercancía
 * que lo justifique. `lotId` solo viene informado cuando la pantalla eligió un
 * lote ya registrado.
 *
 * `baseQuantity` no está aquí: depende de las unidades del artículo y la
 * resuelve el repositorio, igual que en la línea.
 */
class EntryLineLotData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $lotNumber,
        public readonly float $quantity,
        public readonly ?string $lotId = null,
        public readonly ?string $expiresAt = null,
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
            lotNumber: trim((string) ($row['lot_number'] ?? '')),
            quantity: round((float) ($row['quantity'] ?? 0), 4),
            lotId: $row['lot_id'] ?? null,
            expiresAt: $row['expires_at'] ?? null,
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * Las filas de lote de una línea, sin las que llegaron en blanco: una fila
     * sin número ni cantidad es una fila que el usuario abrió y no llenó.
     *
     * @param  mixed  $rows
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
            static fn (self $lot): bool => $lot->lotNumber !== '' || $lot->quantity > 0,
        ));
    }
}
