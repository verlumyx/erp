<?php

declare(strict_types=1);

namespace App\Modules\Item\Resources;

use App\Modules\Item\Models\ItemPrice;
use App\Modules\Item\Models\ItemUnit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Artículo visto como opción de un select remoto (`Select2Ajax`).
 *
 * Es deliberadamente más pobre que `ItemResource`: solo `value`, `label` y los
 * datos que el formulario necesita en el momento de elegir el artículo. Las
 * unidades y los precios van porque de ellos salen la unidad base y el precio
 * sugerido de la línea, sin una segunda ida al servidor.
 *
 * Las relaciones las garantiza `ItemOptionSearchService`.
 */
class ItemOptionResource extends JsonResource
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
                'type' => $this->type,
                'sale_tax_id' => $this->sale_tax_id,
                'purchase_tax_id' => $this->purchase_tax_id,
                'standard_cost' => (string) $this->standard_cost,
                'min_price' => (string) $this->min_price,
                'status' => $this->status,
                'units' => $this->units
                    ->where('status', 'active')
                    ->map(fn (ItemUnit $unit): array => [
                        'measurement_unit_id' => $unit->measurement_unit_id,
                        'name' => $unit->measurementUnit?->name ?? '',
                        'is_base' => $unit->is_base,
                        'conversion_factor' => (string) $unit->conversion_factor,
                    ])
                    ->values()
                    ->all(),
                'prices' => $this->prices
                    ->where('status', 'active')
                    ->map(fn (ItemPrice $price): array => [
                        'price_list_id' => $price->price_list_id,
                        'price' => (string) $price->price,
                        'currency' => $price->currency,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }
}
