<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Store\Controllers\Api\Concerns\ReadsStoreContext;
use App\Modules\Store\Resources\Api\StoreSettingsPublicResource;
use App\Modules\Store\Services\StoreCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Ajustes públicos de la tienda.
 *
 * Nombre, logo, color, contacto, moneda, tasa del día e interruptores. La
 * tienda no tiene nada de la empresa quemado en su código: todo sale de
 * aquí.
 */
class StoreSettingsApiController extends Controller
{
    use ReadsStoreContext;

    public function __construct(
        private readonly StoreCatalogService $catalog,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $settings = $this->settings($request);
        $rate = $this->catalog->todayRate($settings);

        return $this->cached([
            'data' => (new StoreSettingsPublicResource([
                'store_name' => $settings->store_name,
                'logo_url' => $settings->logo_path ? Storage::disk('public')->url($settings->logo_path) : null,
                'brand_color' => $settings->brand_color,
                'contact_phone' => $settings->contact_phone,
                'contact_email' => $settings->contact_email,
                'currency' => $rate['currency'] ?? $this->catalog->storeCurrency($settings),
                'secondary_currency' => $rate['secondary_currency'] ?? null,
                'exchange_rate' => $rate['exchange_rate'] ?? null,
                'shows_stock' => $settings->shows_stock,
                'allows_orders' => $settings->allows_orders,
                'shows_secondary_currency' => $settings->shows_secondary_currency,
            ]))->resolve(),
        ]);
    }
}
