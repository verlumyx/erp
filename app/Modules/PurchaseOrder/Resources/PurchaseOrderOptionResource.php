<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Resources;

use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Orden de compra vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `PurchaseOrderResource`: solo `value`,
 * `label` y lo que la factura necesita saber del documento origen al elegirlo
 * —proveedor, moneda y días de crédito— sin una segunda ida al servidor. Las
 * líneas viajan dentro porque de ellas se arma la entrada que recibe la orden.
 *
 * Las relaciones las garantiza `PurchaseOrderOptionSearchService`.
 */
class PurchaseOrderOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        $reference = $this->supplier_reference !== null ? " · {$this->supplier_reference}" : '';

        return [
            'value' => $this->id,
            'label' => "{$this->code}{$reference}",
            'meta' => [
                'code' => $this->code,
                'supplier_id' => $this->supplier_id,
                'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
                'warehouse_id' => $this->warehouse_id,
                'currency' => $this->currency,
                'payment_term_days' => (int) $this->payment_term_days,
                'order_date' => $this->order_date?->format('Y-m-d'),
                'status' => $this->status,
                'lines' => $this->lines
                    ->where('status', 'active')
                    ->sortBy('line_number')
                    ->map(fn (PurchaseOrderLine $line): array => [
                        'id' => $line->id,
                        'line_number' => $line->line_number,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item?->name,
                        'item_code' => $line->item?->code,
                        'measurement_unit_id' => $line->measurement_unit_id,
                        'measurement_unit_name' => $line->measurementUnit?->name,
                        'quantity' => (string) $line->quantity,
                        /** Lo ya recibido: de ahí sale cuánto queda por llegar. */
                        'received_quantity' => (string) $line->received_quantity,
                        'unit_price' => (string) $line->unit_price,
                        'discount_percent' => (string) $line->discount_percent,
                        'tax_id' => $line->tax_id,
                        'tax_percent' => (string) $line->tax_percent,
                        'withholding_percent' => (string) $line->withholding_percent,
                        'notes' => $line->notes,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
