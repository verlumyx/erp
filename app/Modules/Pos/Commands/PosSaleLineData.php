<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

use App\Modules\SalesInvoice\Commands\SalesInvoiceLineData;

class PosSaleLineData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $itemId,
        public readonly string $measurementUnitId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $discountPercent = 0.0,
        public readonly float $discountAmount = 0.0,
        public readonly ?string $taxId = null,
        public readonly float $taxPercent = 0.0,
        public readonly float $taxAmount = 0.0,
        public readonly float $subtotal = 0.0,
        public readonly float $total = 0.0,
        public readonly ?string $notes = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $quantity = (float) ($row['quantity'] ?? 1);
        $unitPrice = (float) ($row['unit_price'] ?? 0);
        $discountPercent = (float) ($row['discount_percent'] ?? 0);
        $discountAmount = isset($row['discount_amount'])
            ? (float) $row['discount_amount']
            : round(($quantity * $unitPrice) * ($discountPercent / 100), 2);

        $subtotal = isset($row['subtotal'])
            ? (float) $row['subtotal']
            : round(($quantity * $unitPrice) - $discountAmount, 2);

        $taxPercent = (float) ($row['tax_percent'] ?? 0);
        $taxAmount = isset($row['tax_amount'])
            ? (float) $row['tax_amount']
            : round($subtotal * ($taxPercent / 100), 2);

        $total = isset($row['total'])
            ? (float) $row['total']
            : round($subtotal + $taxAmount, 2);

        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            itemId: (string) $row['item_id'],
            measurementUnitId: (string) $row['measurement_unit_id'],
            quantity: $quantity,
            unitPrice: $unitPrice,
            discountPercent: $discountPercent,
            discountAmount: $discountAmount,
            taxId: $row['tax_id'] ?? null,
            taxPercent: $taxPercent,
            taxAmount: $taxAmount,
            subtotal: $subtotal,
            total: $total,
            notes: $row['notes'] ?? null,
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

    public function toInvoiceLine(): SalesInvoiceLineData
    {
        return new SalesInvoiceLineData(
            id: null,
            itemId: $this->itemId,
            measurementUnitId: $this->measurementUnitId,
            quantity: (string) $this->quantity,
            unitPrice: (string) $this->unitPrice,
            discountPercent: (string) $this->discountPercent,
            taxId: $this->taxId,
            taxPercent: (string) $this->taxPercent,
            withholdingPercent: '0',
            notes: $this->notes,
            status: $this->status,
        );
    }
}
