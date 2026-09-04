<?php

declare(strict_types=1);

namespace App\Modules\Import\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'import_id' => $this->import_id,
            'line_number' => $this->line_number,
            /**
             * De dónde sale la línea. Es lo que la identifica entre dos
             * guardados: los ítems no se capturan, se derivan.
             */
            'entry_line_id' => $this->entry_line_id,
            'entry_code' => $this->whenLoaded('entryLine', fn () => $this->entryLine?->entry?->code),
            'item_id' => $this->item_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'item_code' => $this->whenLoaded('item', fn () => $this->item?->code),
            'measurement_unit_id' => $this->measurement_unit_id,
            'measurement_unit_name' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit?->name),
            'location_id' => $this->location_id,
            'location_name' => $this->whenLoaded('location', fn () => $this->location?->name),
            /** Lo aceptado en la entrada y lo que de eso sigue en existencia. */
            'base_quantity' => $this->base_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'unit_cost' => $this->unit_cost,
            'base_value' => $this->base_value,
            'allocation_base' => $this->allocation_base,
            'allocated_amount' => $this->allocated_amount,
            'unit_delta' => $this->unit_delta,
            'new_unit_cost' => $this->new_unit_cost,
            'capitalized_amount' => $this->capitalized_amount,
            'variance_amount' => $this->variance_amount,
            'status' => $this->status,
            'notes' => $this->notes,
            /**
             * El reparto por lote. Se resuelve aquí mismo —igual que
             * `ImportResource` hace con las líneas— porque una colección de
             * recursos sin resolver se serializa envuelta en `data` y la
             * pantalla espera una lista.
             */
            'lots' => $this->whenLoaded(
                'lots',
                fn (): array => ImportLineLotResource::collection($this->lots)->resolve($request),
            ),
        ];
    }
}
