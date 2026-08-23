<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Resources;

use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Factura de compra vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `PurchaseInvoiceResource`: solo `value`,
 * `label` y lo que una nota de crédito o una devolución necesitan saber de la
 * factura al elegirla —proveedor, moneda y sus líneas facturadas— sin una
 * segunda ida al servidor.
 */
class PurchaseInvoiceOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->code} · {$this->supplier_invoice_number}",
            'meta' => [
                'code' => $this->code,
                'supplier_id' => $this->supplier_id,
                'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
                'supplier_invoice_number' => $this->supplier_invoice_number,
                'invoice_date' => $this->invoice_date?->format('Y-m-d'),
                'due_date' => $this->due_date?->format('Y-m-d'),
                'currency' => $this->currency,
                /** Con la tasa que congeló sale el diferencial cambiario al pagarla. */
                'exchange_rate' => $this->exchange_rate,
                'total' => $this->total,
                'paid_amount' => $this->paid_amount,
                'balance' => $this->balance,
                'payment_status' => $this->payment_status,
                'status' => $this->status,
                /** De aquí salen las líneas que la nota acredita y la devolución saca. */
                'lines' => $this->whenLoaded('lines', fn (): array => $this->lines
                    ->map(fn (PurchaseInvoiceLine $line): array => [
                        'id' => $line->id,
                        'line_number' => $line->line_number,
                        'item_id' => $line->item_id,
                        'item_code' => $line->relationLoaded('item') ? $line->item?->code : null,
                        'item_name' => $line->relationLoaded('item') ? $line->item?->name : null,
                        'measurement_unit_id' => $line->measurement_unit_id,
                        'measurement_unit_name' => $line->relationLoaded('measurementUnit')
                            ? $line->measurementUnit?->name
                            : null,
                        'quantity' => $line->quantity,
                        /** Lo ya devuelto: de ahí sale cuánto queda por devolver. */
                        'returned_quantity' => $line->returned_quantity,
                        'unit_price' => $line->unit_price,
                        /** Costo final de la compra; con él se valora la devolución. */
                        'landed_cost' => $line->landed_cost,
                        'discount_percent' => $line->discount_percent,
                        'tax_id' => $line->tax_id,
                        'tax_percent' => $line->tax_percent,
                        'withholding_percent' => $line->withholding_percent,
                        'warehouse_id' => $line->warehouse_id,
                        'lot_id' => $line->lot_id,
                    ])
                    ->values()
                    ->all()),
            ],
        ];
    }
}
