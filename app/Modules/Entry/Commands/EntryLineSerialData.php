<?php

declare(strict_types=1);

namespace App\Modules\Entry\Commands;

/**
 * Una de las unidades con serie que llegan en una línea de la entrada.
 *
 * La serie se ata a su lote por **número de lote**, no por id: el lote puede
 * ser una fila recién capturada que todavía no existe en la base, y el número
 * es único dentro de la línea porque el Request así lo exige.
 */
class EntryLineSerialData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $serialNumber,
        public readonly ?string $serialId = null,
        public readonly ?string $lotNumber = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $lotNumber = trim((string) ($row['lot_number'] ?? ''));

        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            serialNumber: trim((string) ($row['serial_number'] ?? '')),
            serialId: $row['serial_id'] ?? null,
            lotNumber: $lotNumber === '' ? null : $lotNumber,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * Las series de una línea, limpias de vacías y de repetidas: una serie
     * identifica una unidad, así que no puede llegar dos veces en la misma
     * línea.
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

            if ($serial->serialNumber === '' || isset($seen[$serial->serialNumber])) {
                continue;
            }

            $seen[$serial->serialNumber] = true;
            $serials[] = $serial;
        }

        return $serials;
    }
}
