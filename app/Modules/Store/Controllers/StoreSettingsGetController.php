<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Resources\StoreSettingResource;
use App\Modules\Store\Services\StoreSettingFindOrCreateService;
use App\Modules\Store\Services\StoreSettingFormOptionsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Los ajustes de tienda son un singleton por empresa: sin índice ni pantalla
 * de creación, se entra directo a editarlos.
 */
class StoreSettingsGetController extends Controller
{
    public function __construct(
        private readonly StoreSettingFindOrCreateService $findService,
        private readonly StoreSettingFormOptionsService $formOptionsService,
    ) {}

    public function edit(Request $request, string $company): Response
    {
        abort_unless($request->user()?->hasPermission('store-settings.edit') ?? false, 403);

        $settings = $this->findService->execute($company, $request->user()?->id);

        return Inertia::render('store/settings/edit', [
            'settings' => (new StoreSettingResource($settings))->resolve(),
            'options' => $this->formOptionsService->execute($company),
        ]);
    }
}
