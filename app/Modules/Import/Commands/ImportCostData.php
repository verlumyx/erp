<?php

declare(strict_types=1);

namespace App\Modules\Import\Commands;

/**
 * Uno de los cobros que se reparten, tal como llega desde la pantalla.
 *
 * La tasa a la moneda del expediente no viaja: la resuelve el catálogo, igual
 * que en el resto de los documentos. Lo que se captura es el importe en la
 * moneda en la que se cobró.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class ImportCostData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $concept,
        public readonly string $currency,
        public readonly float $amount,
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        public readonly ?string $supplierId = null,
        public readonly ?string $description = null,
        public readonly ?string $notes = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $sourceableType = (string) ($row['sourceable_type'] ?? '');
        $sourceableId = (string) ($row['sourceable_id'] ?? '');

        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            concept: (string) ($row['concept'] ?? 'freight'),
            currency: strtoupper((string) ($row['currency'] ?? '')),
            amount: round((float) ($row['amount'] ?? 0), 2),
            sourceableType: $sourceableType === '' ? null : $sourceableType,
            sourceableId: $sourceableId === '' ? null : $sourceableId,
            supplierId: ($row['supplier_id'] ?? '') === '' ? null : (string) $row['supplier_id'],
            description: $row['description'] ?? null,
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, self>
     */
    public static function collection(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_map(
            static fn (array $row): self => self::fromArray($row),
            array_values(array_filter($rows, 'is_array')),
        );
    }
}
