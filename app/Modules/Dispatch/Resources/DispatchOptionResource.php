<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Resources;

use App\Modules\Dispatch\Models\DispatchLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Despacho visto como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `DispatchResource`: solo `value`, `label` y
 * lo que una factura de venta necesita al elegirlo —cliente, bodega, pedido de
 * origen y lo que el cliente se quedó— sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `DispatchOptionSearchService`.
 */
class DispatchOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->code} · {$this->dispatch_date?->format('Y-m-d')}",
            'meta' => [
                'code' => $this->code,
                'client_id' => $this->client_id,
                'client_name' => $this->client?->name,
                'client_address_id' => $this->client_address_id,
                'warehouse_id' => $this->warehouse_id,
                'sourceable_type' => $this->sourceable_type,
                'sourceable_id' => $this->sourceable_id,
                'dispatch_date' => $this->dispatch_date?->format('Y-m-d'),
                'delivery_status' => $this->delivery_status,
                'status' => $this->status,
                'lines' => $this->lines
                    ->where('status', 'active')
                    ->sortBy('line_number')
                    ->map(fn (DispatchLine $line): array => [
                        'id' => $line->id,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item?->name,
                        'item_sku' => $line->item?->sku,
                        'measurement_unit_id' => $line->measurement_unit_id,
                        'measurement_unit_name' => $line->measurementUnit?->name,
                        'quantity' => (string) $line->quantity,
                        /** Lo que el cliente se quedó: es lo que hay que facturar. */
                        'delivered_quantity' => (string) $line->delivered_quantity,
                        'unit_price' => (string) $line->unit_price,
                        'discount_percent' => (string) $line->discount_percent,
                        'tax_id' => $line->tax_id,
                        'tax_percent' => (string) $line->tax_percent,
                        'withholding_percent' => (string) $line->withholding_percent,
                        'sourceable_type' => $line->sourceable_type,
                        'sourceable_id' => $line->sourceable_id,
                        'lot_id' => $line->lot_id,
                        'notes' => $line->notes,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
