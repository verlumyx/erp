<?php

declare(strict_types=1);

namespace App\Modules\Import\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportEntryResource extends JsonResource
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
            'entry_id' => $this->entry_id,
            'entry_code' => $this->whenLoaded('entry', fn () => $this->entry?->code),
            'entry_date' => $this->whenLoaded('entry', fn () => $this->entry?->entry_date?->format('Y-m-d')),
            'entry_status' => $this->whenLoaded('entry', fn () => $this->entry?->status),
            'supplier_document' => $this->whenLoaded('entry', fn () => $this->entry?->supplier_document),
            'total_cost' => $this->whenLoaded('entry', fn () => $this->entry?->total_cost),
            'status' => $this->status,
        ];
    }
}
