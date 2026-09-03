<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lo que la tienda necesita saber de sí misma: nombre, logo, color, contacto
 * e interruptores. Recibe el arreglo ya armado por el controlador.
 */
class StoreSettingsPublicResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
