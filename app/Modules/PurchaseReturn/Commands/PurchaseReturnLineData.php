<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Commands;

/**
 * Una fila de `app_purchase_return_lines` tal como llega desde la pantalla de
 * la devolución.
 *
 * La pantalla **no captura dinero**: el costo, el impuesto y el descuento se
 * pactaron en la factura de compra, y aquí los pone
 * `PurchaseReturnPricingService` con `withPricing()` —copiándolos de la línea
 * facturada o, sin factura, del costo promedio del artículo—. Un `unit_price`
 * que mande el cliente se ignora: no es una decisión suya.
 *
 * Tampoco captura lo físico: el lote, la serie y la ubicación de la que sale la
 * mercancía los pide el despacho que la devolución genera al confirmarse, que
 * es el documento que de verdad la saca. De la línea se captura qué vuelve,
 * cuánto y de qué bodega.
 *
 * `base_quantity` y `line_number` son la excepción: dependen del artículo o de
 * las líneas ya guardadas, así que los resuelve el repositorio.
 *
 * El `id` solo sirve para reconocer una fila que ya existe: nunca se usa para
 * insertar, así un id ajeno enviado desde el cliente no puede colisionar.
 */
class PurchaseReturnLineData
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $itemId,
        public readonly string $measurementUnitId,
        /** Bodega desde la que sale la mercancía de esta línea. */
        public readonly string $warehouseId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $discountPercent,
        public readonly float $discountAmount,
        public readonly float $taxPercent,
        public readonly float $taxAmount,
        public readonly float $withholdingPercent,
        public readonly float $withholdingAmount,
        public readonly float $subtotal,
        public readonly float $total,
        public readonly ?string $taxId = null,
        /** Línea de la factura que esta línea devuelve. */
        public readonly ?string $purchaseInvoiceLineId = null,
        /** Motivo propio de la línea; sin él manda el de la cabecera. */
        public readonly ?string $reason = null,
        public readonly ?string $notes = null,
        public readonly string $status = 'active',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (string) $row['id'] : null,
            itemId: (string) $row['item_id'],
            measurementUnitId: (string) $row['measurement_unit_id'],
            warehouseId: (string) ($row['warehouse_id'] ?? ''),
            quantity: (float) ($row['quantity'] ?? 0),
            unitPrice: 0.0,
            discountPercent: 0.0,
            discountAmount: 0.0,
            taxPercent: 0.0,
            taxAmount: 0.0,
            withholdingPercent: 0.0,
            withholdingAmount: 0.0,
            subtotal: 0.0,
            total: 0.0,
            taxId: null,
            purchaseInvoiceLineId: $row['purchase_invoice_line_id'] ?? null,
            reason: $row['reason'] ?? null,
            notes: $row['notes'] ?? null,
            status: (string) ($row['status'] ?? 'active'),
        );
    }

    /**
     * La misma línea con el precio y los cargos que le puso el sistema.
     *
     * Los montos se derivan aquí y en ningún otro sitio: el descuento sale del
     * porcentaje, el impuesto del subtotal y la retención del impuesto —no de
     * la base imponible—.
     */
    public function withPricing(
        float $unitPrice,
        ?string $taxId,
        float $taxPercent,
        float $withholdingPercent,
        float $discountPercent,
    ): self {
        $gross = $this->quantity * $unitPrice;

        $discountAmount = round($gross * $discountPercent / 100, 2);
        $subtotal = round($gross - $discountAmount, 2);
        $taxAmount = round($subtotal * $taxPercent / 100, 2);

        return new self(
            id: $this->id,
            itemId: $this->itemId,
            measurementUnitId: $this->measurementUnitId,
            warehouseId: $this->warehouseId,
            quantity: $this->quantity,
            unitPrice: $unitPrice,
            discountPercent: $discountPercent,
            discountAmount: $discountAmount,
            taxPercent: $taxPercent,
            taxAmount: $taxAmount,
            withholdingPercent: $withholdingPercent,
            withholdingAmount: round($taxAmount * $withholdingPercent / 100, 2),
            subtotal: $subtotal,
            total: round($subtotal + $taxAmount, 2),
            taxId: $taxId,
            purchaseInvoiceLineId: $this->purchaseInvoiceLineId,
            reason: $this->reason,
            notes: $this->notes,
            status: $this->status,
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
