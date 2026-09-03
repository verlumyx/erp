<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Commands;

/**
 * Una de las unidades con serie que nombra una línea del ajuste.
 *
 * La serie se ata a su lote por el id del lote, que es único dentro de la
 * línea: no hace falta el id de la fila de detalle, que puede no existir
 * todavía cuando la línea se acaba de capturar.
 */
class AdjustmentLineSerialData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $serialId,
        public readonly ?string $lotId = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $lotId = (string) ($row['lot_id'] ?? '');

        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            serialId: (string) ($row['serial_id'] ?? ''),
            lotId: $lotId === '' ? null : $lotId,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * Las series de una línea, sin vacías y sin repetidas: una serie identifica
     * una unidad, así que no puede contarse dos veces en la misma línea.
     *
     * @return array<int, self>
     */
    public static function collection(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $serials = [];
        $seen = [];

        foreach (array_filter($rows, 'is_array') as $row) {
            $serial = self::fromArray($row);

            if ($serial->serialId === '' || isset($seen[$serial->serialId])) {
                continue;
            }

            $seen[$serial->serialId] = true;
            $serials[] = $serial;
        }

        return $serials;
    }
}
