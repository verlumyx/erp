<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

class PosDenominationData
{
    public function __construct(
        public readonly string $currency,
        public readonly float $denomination,
        public readonly int $count,
        public readonly float $totalAmount,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $denomination = (float) ($row['denomination'] ?? 0);
        $count = (int) ($row['count'] ?? 0);
        $total = isset($row['total_amount']) ? (float) $row['total_amount'] : round($denomination * $count, 2);

        return new self(
            currency: strtoupper((string) ($row['currency'] ?? 'USD')),
            denomination: $denomination,
            count: $count,
            totalAmount: $total,
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
