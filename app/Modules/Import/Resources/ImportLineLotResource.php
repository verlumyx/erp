<?php

declare(strict_types=1);

namespace App\Modules\Import\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportLineLotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'import_line_id' => $this->import_line_id,
            'line_number' => $this->line_number,
            'entry_line_lot_id' => $this->entry_line_lot_id,
            'lot_id' => $this->lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            /** Lo aceptado de esa caja y lo que de eso sigue en la bodega. */
            'base_quantity' => $this->base_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'allocation_base' => $this->allocation_base,
            'allocated_amount' => $this->allocated_amount,
            'unit_delta' => $this->unit_delta,
            'new_unit_cost' => $this->new_unit_cost,
            'capitalized_amount' => $this->capitalized_amount,
            'variance_amount' => $this->variance_amount,
            'status' => $this->status,
        ];
    }
}
