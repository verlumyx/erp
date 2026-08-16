<?php

declare(strict_types=1);

namespace App\Modules\Item\Commands;

/**
 * Una fila de `app_item_units` tal como llega desde la pantalla del artículo.
 */
class ItemUnitData
{
    public function __construct(
        public readonly string $measurementUnitId,
        public readonly string $isBase,
        public readonly string $conversionFactor,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $isBase = ($row['is_base'] ?? 'no') === 'yes' ? 'yes' : 'no';

        return new self(
            measurementUnitId: (string) $row['measurement_unit_id'],
            isBase: $isBase,
            // La unidad base siempre convierte 1:1 contra sí misma.
            conversionFactor: $isBase === 'yes' ? '1' : (string) ($row['conversion_factor'] ?? 1),
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
