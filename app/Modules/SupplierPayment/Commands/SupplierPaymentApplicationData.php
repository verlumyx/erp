<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Commands;

/**
 * Una fila del reparto tal como llega desde la pantalla del pago: qué factura
 * se abona y por cuánto.
 *
 * No trae `exchange_difference` ni `applied_at`: los resuelve el backend con la
 * tasa de la factura y la del pago cuando el pago se confirma.
 */
class SupplierPaymentApplicationData
{
    public function __construct(
        public readonly string $purchaseInvoiceId,
        public readonly float $appliedAmount,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            purchaseInvoiceId: (string) $row['purchase_invoice_id'],
            appliedAmount: round((float) ($row['applied_amount'] ?? 0), 2),
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
