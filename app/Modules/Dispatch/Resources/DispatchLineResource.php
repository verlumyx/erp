<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispatch_id' => $this->dispatch_id,
            'line_number' => $this->line_number,
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            /**
             * La trazabilidad vive en sus propias tablas: la línea solo la
             * agrupa. Se resuelve aquí mismo —igual que `DispatchResource` hace
             * con las líneas— porque una colección de recursos sin resolver se
             * serializa envuelta en `data` y la pantalla espera una lista.
             */
            'lots' => $this->whenLoaded(
                'lots',
                fn (): array => DispatchLineLotResource::collection($this->lots)->resolve($request),
            ),
            'serials' => $this->whenLoaded(
                'serials',
                fn (): array => DispatchLineSerialResource::collection($this->serials)->resolve($request),
            ),
            /** Lo que pidió la línea del pedido. Vacío en una línea sin origen. */
            'source_quantity' => $this->whenLoaded('sourceable', fn () => $this->sourceable?->quantity),
            'location_id' => $this->location_id,
            'location_name' => $this->whenLoaded('location', fn () => $this->location?->name),
            'quantity' => $this->quantity,
            'base_quantity' => $this->base_quantity,
            'unit_price' => $this->unit_price,
            'discount_percent' => $this->discount_percent,
            'discount_amount' => $this->discount_amount,
            'tax_id' => $this->tax_id,
            'tax_percent' => $this->tax_percent,
            'tax_amount' => $this->tax_amount,
            'withholding_percent' => $this->withholding_percent,
            'withholding_amount' => $this->withholding_amount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'delivered_quantity' => $this->delivered_quantity,
            'returned_quantity' => $this->returned_quantity,
            /** Costo con el que salió: en borrador el promedio, confirmado el real. */
            'unit_cost' => $this->unit_cost,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
