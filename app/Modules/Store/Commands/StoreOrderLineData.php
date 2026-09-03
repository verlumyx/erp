<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

/**
 * Una línea del pedido web. De la tienda llegan solo `slug` y `quantity`; el
 * resto —artículo, publicación, unidad base y precio— lo resuelve
 * `StoreOrderCreateService` contra el ERP, nunca la tienda.
 */
class StoreOrderLineData
{
    public function __construct(
        public readonly string $slug,
        public readonly string $quantity,
        public readonly ?string $itemId = null,
        public readonly ?string $storeItemId = null,
        public readonly ?string $measurementUnitId = null,
        public readonly string $unitPrice = '0',
        public readonly ?string $currency = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            slug: (string) $row['slug'],
            quantity: (string) ($row['quantity'] ?? 0),
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

    /** La misma línea con lo que el ERP resolvió para ella. */
    public function priced(string $itemId, string $storeItemId, string $measurementUnitId, string $unitPrice, string $currency): self
    {
        return new self(
            slug: $this->slug,
            quantity: $this->quantity,
            itemId: $itemId,
            storeItemId: $storeItemId,
            measurementUnitId: $measurementUnitId,
            unitPrice: $unitPrice,
            currency: $currency,
        );
    }
}
