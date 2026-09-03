<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreItemImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_item_id' => $this->store_item_id,
            'path' => $this->path,
            'url' => $this->url(),
            'alt_text' => $this->alt_text,
            'order' => $this->order,
            'width' => $this->width,
            'height' => $this->height,
            'status' => $this->status,
        ];
    }
}
