<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchLineLotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispatch_line_id' => $this->dispatch_line_id,
            'line_number' => $this->line_number,
            'lot_id' => $this->lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            'quantity' => $this->quantity,
            'base_quantity' => $this->base_quantity,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
