<?php

declare(strict_types=1);

namespace App\Modules\Entry\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryLineLotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_line_id' => $this->entry_line_id,
            'line_number' => $this->line_number,
            'lot_number' => $this->lot_number,
            /** Vacío mientras la entrada sea un borrador: el lote nace al confirmar. */
            'lot_id' => $this->lot_id,
            'lot_code' => $this->whenLoaded('lot', fn () => $this->lot?->code),
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'quantity' => $this->quantity,
            'base_quantity' => $this->base_quantity,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
