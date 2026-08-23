<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proveedor visto como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `SupplierResource`: solo `value`, `label` y
 * lo que la orden de compra copia en su cabecera al elegirlo —su moneda y sus
 * días de crédito—, sin una segunda ida al servidor.
 */
class SupplierOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->code} · {$this->name}",
            'meta' => [
                'code' => $this->code,
                'name' => $this->name,
                'currency' => $this->currency,
                'payment_term_days' => (int) $this->payment_term_days,
                /**
                 * Los dos indicadores que la pantalla de pagos muestra junto
                 * al proveedor: lo que se le debe y el crédito que ya tiene a
                 * favor.
                 */
                'current_balance' => (string) $this->current_balance,
                'advance_balance' => (string) $this->advance_balance,
                'status' => $this->status,
            ],
        ];
    }
}
