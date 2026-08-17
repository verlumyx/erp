<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationResource extends JsonResource
{
    /**
     * `dual_currency` viaja resuelto para que el frontend no tenga que
     * comparar monedas en cada pantalla que muestra importes.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'base_currency' => $this->base_currency,
            'secondary_currency' => $this->secondary_currency,
            'dual_currency' => $this->usesDualCurrency(),
            'rate_type' => $this->rate_type,
            'allows_rate_override' => $this->allows_rate_override,
            'amount_decimals' => $this->amount_decimals,
            'price_decimals' => $this->price_decimals,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
