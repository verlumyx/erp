<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Devolución vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `PurchaseReturnResource`: solo `value`,
 * `label` y lo que una nota de crédito necesita saber de la devolución al
 * elegirla —proveedor, factura de origen, moneda y total— sin una segunda ida
 * al servidor.
 *
 * Las relaciones las garantiza `PurchaseReturnOptionSearchService`.
 */
class PurchaseReturnOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->code} · {$this->return_date?->format('Y-m-d')}",
            'meta' => [
                'code' => $this->code,
                'supplier_id' => $this->supplier_id,
                'supplier_name' => $this->supplier?->name,
                'purchase_invoice_id' => $this->purchase_invoice_id,
                'return_date' => $this->return_date?->format('Y-m-d'),
                'reason' => $this->reason,
                'currency' => $this->currency,
                /** Con la tasa que congeló se reexpresa lo devuelto. */
                'exchange_rate' => $this->exchange_rate,
                'total' => $this->total,
                'status' => $this->status,
            ],
        ];
    }
}
