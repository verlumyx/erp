<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Resources;

use App\Modules\SalesReturn\Models\SalesReturnLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Devolución vista como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `SalesReturnResource`: solo `value`,
 * `label` y lo que una nota de crédito necesita saber de la devolución al
 * elegirla —cliente, factura de origen, moneda y total— sin una segunda ida
 * al servidor.
 *
 * Las relaciones las garantiza `SalesReturnOptionSearchService`.
 */
class SalesReturnOptionResource extends JsonResource
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
                'client_id' => $this->client_id,
                'client_name' => $this->client?->name,
                'sales_invoice_id' => $this->sales_invoice_id,
                'return_date' => $this->return_date?->format('Y-m-d'),
                'reason' => $this->reason,
                'condition' => $this->condition,
                'currency' => $this->currency,
                /** Con la tasa que congeló se reexpresa lo devuelto. */
                'exchange_rate' => $this->exchange_rate,
                'total' => $this->total,
                'status' => $this->status,
                'lines' => $this->relationLoaded('lines') ? $this->lines
                    ->where('status', 'active')
                    ->sortBy('line_number')
                    ->map(fn (SalesReturnLine $line): array => [
                        'id' => $line->id,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item?->name,
                        'item_sku' => $line->item?->sku,
                        'item_code' => $line->item?->code,
                        'measurement_unit_id' => $line->measurement_unit_id,
                        'measurement_unit_name' => $line->measurementUnit?->name,
                        'quantity' => (string) $line->quantity,
                        'unit_cost' => (string) $line->unit_cost,
                        'location_id' => $line->location_id,
                    ])
                    ->values()
                    ->all() : [],
            ],
        ];
    }
}
