<?php

declare(strict_types=1);

namespace App\Modules\Item\Commands;

/**
 * Una fila de `app_item_prices` tal como llega desde la pantalla del artículo.
 */
class ItemPriceData
{
    public function __construct(
        public readonly string $priceListId,
        public readonly string $price,
        public readonly string $currency,
        public readonly ?string $validFrom = null,
        public readonly ?string $validTo = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            priceListId: (string) $row['price_list_id'],
            price: (string) ($row['price'] ?? 0),
            currency: strtoupper((string) $row['currency']),
            validFrom: $row['valid_from'] ?? null,
            validTo: $row['valid_to'] ?? null,
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
