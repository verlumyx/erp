<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentLineSerialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adjustment_line_id' => $this->adjustment_line_id,
            'adjustment_line_lot_id' => $this->adjustment_line_lot_id,
            'line_number' => $this->line_number,
            'serial_id' => $this->serial_id,
            'serial_number' => $this->whenLoaded('serial', fn () => $this->serial?->serial_number),
            'status' => $this->status,
        ];
    }
}
