<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Resources;

use App\Modules\Transfer\Models\TransferLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Traslado visto como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `TransferResource`: solo `value`, `label` y
 * lo que otro documento necesita al elegirlo —las bodegas, el estado del viaje
 * y lo que se movió— sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `TransferOptionSearchService`.
 */
class TransferOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->code} · {$this->transfer_date?->format('Y-m-d')}",
            'meta' => [
                'code' => $this->code,
                'origin_warehouse_id' => $this->origin_warehouse_id,
                'origin_warehouse_name' => $this->originWarehouse?->name,
                'destination_warehouse_id' => $this->destination_warehouse_id,
                'destination_warehouse_name' => $this->destinationWarehouse?->name,
                'transit_warehouse_id' => $this->transit_warehouse_id,
                'transfer_date' => $this->transfer_date?->format('Y-m-d'),
                'reason' => $this->reason,
                'transfer_status' => $this->transfer_status,
                'status' => $this->status,
                'lines' => $this->lines
                    ->where('status', 'active')
                    ->sortBy('line_number')
                    ->map(fn (TransferLine $line): array => [
                        'id' => $line->id,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item?->name,
                        'item_sku' => $line->item?->sku,
                        'measurement_unit_id' => $line->measurement_unit_id,
                        'measurement_unit_name' => $line->measurementUnit?->name,
                        'quantity' => (string) $line->quantity,
                        'sent_quantity' => (string) $line->sent_quantity,
                        /** Lo que llegó de verdad al destino. */
                        'received_quantity' => (string) $line->received_quantity,
                        'difference_quantity' => (string) $line->difference_quantity,
                        'unit_cost' => (string) $line->unit_cost,
                        'lot_id' => $line->lot_id,
                        'serial_id' => $line->serial_id,
                        'notes' => $line->notes,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
