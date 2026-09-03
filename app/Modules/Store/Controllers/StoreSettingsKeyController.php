<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Services\StoreSettingGenerateKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Genera la llave de la tienda. La llave en claro viaja una sola vez en el
 * flash `store_api_key`: la pantalla la muestra en un diálogo y nunca vuelve
 * a poder leerse.
 */
class StoreSettingsKeyController extends Controller
{
    public function __construct(
        private readonly StoreSettingGenerateKeyService $generateKeyService,
    ) {}

    public function __invoke(Request $request, string $company): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('store-settings.edit') ?? false, 403);

        $plainKey = $this->generateKeyService->execute($company, $request->user()?->id);

        return redirect()
            ->route('store-settings.edit', ['company' => $company])
            ->with('success', 'Llave generada.')
            ->with('store_api_key', $plainKey);
    }
}
