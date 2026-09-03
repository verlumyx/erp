<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Store\Models\StoreCustomer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Comprador autenticado en la tienda: token Sanctum con la habilidad
 * `store-customer`, emitido a un comprador activo de la misma empresa que la
 * llave. Va siempre después de `store.key`.
 */
class AuthenticateStoreCustomer
{
    public const REQUEST_ATTRIBUTE = 'store_customer';

    /** Guard de Sanctum atado al provider de compradores (`config/auth.php`). */
    public const GUARD = 'store';

    public function handle(Request $request, Closure $next): Response
    {
        $customer = self::resolve($request);

        if (! $customer instanceof StoreCustomer) {
            return response()->json(['message' => 'Inicia sesión para continuar.'], 401);
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $customer);

        return $next($request);
    }

    /**
     * El comprador del token, si lo hay y sirve. Es estático para que los
     * endpoints públicos del catálogo —que no exigen sesión— puedan leerlo
     * de forma opcional y mostrar los precios de la lista de su cliente.
     */
    public static function resolve(Request $request): ?StoreCustomer
    {
        $customer = $request->user(self::GUARD);

        if (! $customer instanceof StoreCustomer) {
            return null;
        }

        if (! $customer->tokenCan(StoreCustomer::TOKEN_ABILITY)) {
            return null;
        }

        if ($customer->status !== 'active') {
            return null;
        }

        $companyId = (string) $request->attributes->get(AuthenticateStoreKey::REQUEST_ATTRIBUTE, '');

        if ($companyId === '' || $customer->company_id !== $companyId) {
            return null;
        }

        return $customer;
    }
}
