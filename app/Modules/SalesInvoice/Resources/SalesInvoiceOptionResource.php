<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Resources;

use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Factura de venta vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `SalesInvoiceResource`: solo `value`,
 * `label` y lo que un cobro o una nota de crédito necesitan saber de la factura
 * al elegirla —cliente, moneda, tasa congelada, saldo y, cuando el select las
 * pide, sus líneas facturadas— sin una segunda ida al servidor.
 */
class SalesInvoiceOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => $this->invoice_number ? "{$this->code} · {$this->invoice_number}" : $this->code,
            'meta' => [
                'code' => $this->code,
                'client_id' => $this->client_id,
                'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
                'invoice_number' => $this->invoice_number,
                'invoice_date' => $this->invoice_date?->format('Y-m-d'),
                'due_date' => $this->due_date?->format('Y-m-d'),
                'currency' => $this->currency,
                /** Con la tasa que congeló sale el diferencial cambiario al cobrarla. */
                'exchange_rate' => $this->exchange_rate,
                'total' => $this->total,
                'paid_amount' => $this->paid_amount,
                'balance' => $this->balance,
                'payment_status' => $this->payment_status,
                'status' => $this->status,
                /**
                 * De aquí salen las líneas que una nota de crédito acredita.
                 * Solo viajan si quien buscó las cargó: un cobro no las pide.
                 */
                'lines' => $this->whenLoaded('lines', fn (): array => $this->lines
                    ->map(fn (SalesInvoiceLine $line): array => [
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
                        /** Lo ya devuelto: de ahí sale cuánto queda por acreditar. */
                        'returned_quantity' => $line->returned_quantity,
                        'unit_price' => $line->unit_price,
                        /** Costo congelado de la venta: con él reingresa la mercancía. */
                        'unit_cost' => $line->unit_cost,
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
