<?php

declare(strict_types=1);

namespace App\Modules\Entry\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryLineSerialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_line_id' => $this->entry_line_id,
            'entry_line_lot_id' => $this->entry_line_lot_id,
            'line_number' => $this->line_number,
            'serial_number' => $this->serial_number,
            /** Vacío mientras la entrada sea un borrador: la serie nace al confirmar. */
            'serial_id' => $this->serial_id,
            'status' => $this->status,
        ];
    }
}
