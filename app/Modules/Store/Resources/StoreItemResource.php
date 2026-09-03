<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources;

use App\Modules\Store\Models\StoreItemImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreItemResource extends JsonResource
{
    /**
     * `price` y `availability` no viven en la tabla: los pone
     * `StoreItemFindService` como atributos sueltos. El listado no los carga
     * y viajan en `null`.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->item;

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'item_id' => $this->item_id,
            'item' => $item ? [
                'id' => $item->id,
                'code' => $item->code,
                'sku' => $item->sku,
                'name' => $item->name,
                'type' => $item->type,
                'status' => $item->status,
                'is_sellable' => $item->is_sellable,
                'description' => $item->description,
                'category' => $item->category ? [
                    'id' => $item->category->id,
                    'name' => $item->category->name,
                ] : null,
            ] : null,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->description,
            'is_featured' => $this->is_featured,
            'order' => $this->order,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'images' => $this->whenLoaded('images', fn () => $this->images
                ->map(fn (StoreItemImage $image): array => (new StoreItemImageResource($image))->resolve())
                ->values()
                ->all(), []),
            'price' => $this->getAttribute('store_price'),
            'availability' => $this->getAttribute('store_availability'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
