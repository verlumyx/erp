<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Store\Models\StoreSetting;
use App\Modules\Store\Repositories\Contracts\StoreSettingRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Llave de acceso de la tienda en línea.
 *
 * La tienda no conoce ni expone el `company_id`: la cabecera `X-Store-Key`
 * determina la empresa. Se guarda el SHA-256 de la llave, nunca la llave, y
 * la empresa activa queda en la sesión en memoria —como hace
 * `EnsureUserBelongsToCompanyApi`— para reutilizar la lógica que la lee.
 */
class AuthenticateStoreKey
{
    public const HEADER = 'X-Store-Key';

    public const REQUEST_ATTRIBUTE = 'store_company_id';

    public const SETTINGS_ATTRIBUTE = 'store_settings';

    /** Se anota el último uso como máximo una vez por minuto. */
    private const LAST_USED_THROTTLE_SECONDS = 60;

    public function __construct(
        private readonly StoreSettingRepositoryInterface $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header(self::HEADER, ''));

        if ($key === '') {
            return $this->forbidden();
        }

        $settings = $this->settings->findByApiKeyHash(hash('sha256', $key));

        if (! $settings instanceof StoreSetting || ! $this->isServing($settings)) {
            return $this->forbidden();
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $settings->company_id);
        $request->attributes->set(self::SETTINGS_ATTRIBUTE, $settings);
        session()->put('current_company_id', $settings->company_id);

        $this->touchLastUsed($settings);

        return $next($request);
    }

    private function isServing(StoreSetting $settings): bool
    {
        return $settings->is_enabled === 'yes'
            && $settings->company?->status === 'active';
    }

    private function touchLastUsed(StoreSetting $settings): void
    {
        $lastUsed = $settings->api_key_last_used_at;

        if ($lastUsed !== null && $lastUsed->diffInSeconds(now()) < self::LAST_USED_THROTTLE_SECONDS) {
            return;
        }

        $this->settings->touchApiKeyLastUsedAt($settings);
    }

    private function forbidden(): Response
    {
        return response()->json(['message' => 'La tienda no está disponible.'], 403);
    }
}
