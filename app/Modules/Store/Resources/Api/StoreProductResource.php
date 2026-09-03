<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Producto de la tienda. La forma la arma `StoreCatalogService`; aquí solo
 * se publica tal cual, para que la API y el servicio digan lo mismo.
 */
class StoreProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
