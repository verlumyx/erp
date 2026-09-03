<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\UpdateStoreSettingsCommand;
use App\Modules\Store\Requests\UpdateStoreSettingsRequest;
use App\Modules\Store\Services\StoreSettingUpdateService;
use Illuminate\Http\RedirectResponse;

class StoreSettingsPutController extends Controller
{
    public function __construct(
        private readonly StoreSettingUpdateService $updateService,
    ) {}

    public function __invoke(UpdateStoreSettingsRequest $request, string $company): RedirectResponse
    {
        $this->updateService->execute(
            $company,
            UpdateStoreSettingsCommand::fromRequest($request),
            $request->file('logo'),
            $request->string('remove_logo')->toString() === 'yes',
            $request->user()?->id,
        );

        return redirect()
            ->route('store-settings.edit', ['company' => $company])
            ->with('success', 'Ajustes de la tienda actualizados.');
    }
}
