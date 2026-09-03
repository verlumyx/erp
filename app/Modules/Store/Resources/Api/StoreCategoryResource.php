<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Categoría con al menos una publicación visible y cuántas tiene. */
class StoreCategoryResource extends JsonResource
{
    /**
     * @return array{id: string, name: string, count: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource['id'],
            'name' => (string) $this->resource['name'],
            'count' => (int) $this->resource['count'],
        ];
    }
}
