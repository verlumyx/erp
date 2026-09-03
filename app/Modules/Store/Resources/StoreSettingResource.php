<?php

declare(strict_types=1);

namespace App\Modules\Store\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StoreSettingResource extends JsonResource
{
    /**
     * El hash de la llave nunca viaja: la pantalla solo necesita saber si hay
     * una llave y cuándo se usó por última vez.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'is_enabled' => $this->is_enabled,
            'store_name' => $this->store_name,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
            'brand_color' => $this->brand_color,
            'price_list_id' => $this->price_list_id,
            'price_list' => $this->priceList ? [
                'id' => $this->priceList->id,
                'name' => $this->priceList->name,
            ] : null,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ] : null,
            'shows_stock' => $this->shows_stock,
            'allows_orders' => $this->allows_orders,
            'default_client_type_id' => $this->default_client_type_id,
            'default_client_type' => $this->defaultClientType ? [
                'id' => $this->defaultClientType->id,
                'name' => $this->defaultClientType->name,
            ] : null,
            'shows_secondary_currency' => $this->shows_secondary_currency,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'store_url' => $this->store_url,
            'has_api_key' => $this->hasApiKey(),
            'api_key_last_used_at' => $this->api_key_last_used_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
