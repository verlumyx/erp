<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Artículo publicable visto como opción del select remoto (`Select2Ajax`).
 * Lleva lo que la pantalla precarga al elegirlo: título y descripción.
 */
class StoreItemPublishableOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->code} — {$this->name}",
            'meta' => [
                'code' => $this->code,
                'sku' => $this->sku,
                'name' => $this->name,
                'description' => $this->description,
                'category_name' => $this->category?->name,
            ],
        ];
    }
}
