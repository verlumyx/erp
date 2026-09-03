<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers\Api\Concerns;

use App\Http\Middleware\AuthenticateStoreCustomer;
use App\Http\Middleware\AuthenticateStoreKey;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lo que todo endpoint público comparte: los ajustes que dejó `store.key`,
 * el comprador opcional del token y la caché de lectura.
 */
trait ReadsStoreContext
{
    private const CACHE_CONTROL = 'public, max-age=60';

    protected function settings(Request $request): StoreSetting
    {
        return $request->attributes->get(AuthenticateStoreKey::SETTINGS_ATTRIBUTE);
    }

    /**
     * El comprador es opcional en el catálogo: con sesión ve los precios de
     * la lista de su cliente; sin ella, los de la tienda.
     */
    protected function customer(Request $request): ?StoreCustomer
    {
        return AuthenticateStoreCustomer::resolve($request);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function cached(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)->header('Cache-Control', self::CACHE_CONTROL);
    }
}
