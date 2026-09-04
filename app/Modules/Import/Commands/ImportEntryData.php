<?php

declare(strict_types=1);

namespace App\Modules\Import\Commands;

/**
 * Una de las recepciones que el expediente costea.
 *
 * Solo viaja el id de la entrada: todo lo demás —qué llegó, en qué unidad, a
 * qué costo— lo lee el backend de la propia entrada. Dejar que el cliente lo
 * declarara sería dejarle declarar el costo del inventario.
 */
class ImportEntryData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $entryId,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            entryId: (string) ($row['entry_id'] ?? ''),
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * Las recepciones enviadas, sin las que llegaron vacías: una fila que el
     * usuario abrió y no llenó no es una recepción.
     *
     * @return array<int, self>
     */
    public static function collection(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $entries = array_map(
            static fn (array $row): self => self::fromArray($row),
            array_values(array_filter($rows, 'is_array')),
        );

        return array_values(array_filter(
            $entries,
            static fn (self $entry): bool => $entry->entryId !== '',
        ));
    }

    /**
     * @param  array<int, self>  $entries
     * @return array<int, string>
     */
    public static function activeIds(array $entries): array
    {
        $ids = [];

        foreach ($entries as $entry) {
            if ($entry->status === 'active') {
                $ids[$entry->entryId] = $entry->entryId;
            }
        }

        return array_values($ids);
    }
}
