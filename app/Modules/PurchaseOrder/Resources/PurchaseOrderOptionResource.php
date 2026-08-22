<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Orden de compra vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `PurchaseOrderResource`: solo `value`,
 * `label` y lo que la factura necesita saber del documento origen al elegirlo
 * —proveedor, moneda y días de crédito— sin una segunda ida al servidor.
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
            ],
        ];
    }
}
