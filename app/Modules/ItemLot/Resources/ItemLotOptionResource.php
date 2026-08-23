<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lote visto como opción de un select remoto (`Select2Ajax`).
 *
 * Lleva el artículo y la fecha de vencimiento porque son lo que el formulario
 * necesita en el momento de elegir el lote: comprobar que corresponde a la
 * línea y mostrar cuál vence primero, sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `ItemLotOptionSearchService`.
 */
class ItemLotOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->lot_number} — {$this->code}",
            'meta' => [
                'code' => $this->code,
                'lot_number' => $this->lot_number,
                'item_id' => $this->item_id,
                'item_name' => $this->item?->name ?? '',
                'expires_at' => $this->expires_at?->format('Y-m-d'),
                'status' => $this->status,
            ],
        ];
    }
}
