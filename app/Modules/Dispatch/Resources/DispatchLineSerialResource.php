<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchLineSerialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispatch_line_id' => $this->dispatch_line_id,
            'dispatch_line_lot_id' => $this->dispatch_line_lot_id,
            'line_number' => $this->line_number,
            'serial_id' => $this->serial_id,
            'serial_number' => $this->whenLoaded('serial', fn () => $this->serial?->serial_number),
            'status' => $this->status,
        ];
    }
}
