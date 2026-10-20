<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

class PosPaymentData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $paymentMethod,
        public readonly string $currency,
        public readonly float $exchangeRate,
        public readonly float $amountTendered,
        public readonly float $amountApplied,
        public readonly float $amountInDocumentCurrency,
        public readonly ?string $reference = null,
        public readonly ?string $cardLastDigits = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row, float $defaultRate = 1.0, string $documentCurrency = 'USD'): self
    {
        $currency = strtoupper((string) ($row['currency'] ?? $documentCurrency));
        $exchangeRate = (float) ($row['exchange_rate'] ?? ($currency === $documentCurrency ? 1.0 : $defaultRate));
        $tendered = (float) ($row['amount_tendered'] ?? 0);
        $applied = (float) ($row['amount_applied'] ?? $tendered);

        // Convert to document currency (USD)
        if ($currency === $documentCurrency) {
            $inDocCurrency = $applied;
        } else {
            // If currency is VES and doc is USD, divide by rate (or multiply if defined so)
            $inDocCurrency = $exchangeRate > 0 ? round($applied / $exchangeRate, 2) : $applied;
        }

        if (isset($row['amount_in_document_currency'])) {
            $inDocCurrency = (float) $row['amount_in_document_currency'];
        }

        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            paymentMethod: (string) ($row['payment_method'] ?? 'cash'),
            currency: $currency,
            exchangeRate: $exchangeRate,
            amountTendered: $tendered,
            amountApplied: $applied,
            amountInDocumentCurrency: $inDocCurrency,
            reference: isset($row['reference']) ? (string) $row['reference'] : null,
            cardLastDigits: isset($row['card_last_digits']) ? (string) $row['card_last_digits'] : null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, self>
     */
    public static function collection(array $rows, float $defaultRate = 1.0, string $documentCurrency = 'USD'): array
    {
        return array_map(
            static fn (array $row): self => self::fromArray($row, $defaultRate, $documentCurrency),
            array_values($rows)
        );
    }
}
