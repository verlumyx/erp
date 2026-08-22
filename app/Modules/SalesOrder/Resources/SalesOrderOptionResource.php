<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Resources;

use App\Modules\SalesOrder\Models\SalesOrderLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pedido de venta visto como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `SalesOrderResource`: solo `value`, `label`
 * y lo que la factura copia al elegirlo. Las líneas pendientes viajan dentro
 * porque de ellas se arma la factura sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `SalesOrderOptionSearchService`.
 */
class SalesOrderOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => $this->client?->name
                ? "{$this->code} — {$this->client->name}"
                : (string) $this->code,
            'meta' => [
                'code' => $this->code,
                'client_id' => $this->client_id,
                'client_name' => $this->client?->name,
                'client_address_id' => $this->client_address_id,
                'warehouse_id' => $this->warehouse_id,
                'salesperson_id' => $this->salesperson_id,
                'currency' => $this->currency,
                'payment_term_days' => (int) $this->payment_term_days,
                'status' => $this->status,
                'lines' => $this->lines
                    ->where('status', 'active')
                    ->sortBy('line_number')
                    ->map(fn (SalesOrderLine $line): array => [
                        'id' => $line->id,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item?->name,
                        'item_sku' => $line->item?->sku,
                        'measurement_unit_id' => $line->measurement_unit_id,
                        'measurement_unit_name' => $line->measurementUnit?->name,
                        'quantity' => (string) $line->quantity,
                        'invoiced_quantity' => (string) $line->invoiced_quantity,
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
