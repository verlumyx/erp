<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentLineLotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adjustment_line_id' => $this->adjustment_line_id,
            'line_number' => $this->line_number,
            'lot_id' => $this->lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            /** Lo contado en el lote y lo que el sistema decía de él. */
            'counted_quantity' => $this->counted_quantity,
            'system_quantity' => $this->system_quantity,
            'difference_quantity' => $this->difference_quantity,
            'base_quantity' => $this->base_quantity,
            'movement_type' => $this->movement_type,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
