<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serie vista como opción de un select remoto (`Select2Ajax`).
 *
 * Lleva el artículo, el lote y la bodega porque son lo que el formulario
 * necesita en el instante de elegirla: comprobar que corresponde a la línea y
 * saber de dónde sale la unidad, sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `ItemSerialOptionSearchService`.
 */
class ItemSerialOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string, meta: array<string, mixed>}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->serial_number} — {$this->code}",
            'meta' => [
                'code' => $this->code,
                'serial_number' => $this->serial_number,
                'item_id' => $this->item_id,
                'item_name' => $this->item?->name ?? '',
                'lot_id' => $this->lot_id,
                'warehouse_id' => $this->warehouse_id,
                'status' => $this->status,
            ],
        ];
    }
}
