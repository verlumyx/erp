<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Commands;

/**
 * Lo que llegó de una línea a la bodega de destino, en la unidad de la línea.
 *
 * La línea se reconoce por su `id`, no por su posición: la recepción se
 * registra sobre un traslado ya confirmado y sus líneas no se reordenan.
 */
class TransferReceiptLineData
{
    public function __construct(
        public readonly string $id,
        public readonly float $receivedQuantity,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            receivedQuantity: round((float) ($row['received_quantity'] ?? 0), 4),
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
