<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Configuration\Commands\UpdateConfigurationCommand;
use App\Modules\Configuration\Requests\UpdateConfigurationRequest;
use App\Modules\Configuration\Services\ConfigurationUpdateService;
use Illuminate\Http\RedirectResponse;

class ConfigurationPutController extends Controller
{
    public function __construct(
        private readonly ConfigurationUpdateService $updateService,
    ) {}

    public function __invoke(UpdateConfigurationRequest $request, string $company): RedirectResponse
    {
        $this->updateService->execute(
            $company,
            UpdateConfigurationCommand::fromRequest($request),
        );

        return redirect()
            ->route('configuration.edit', ['company' => $company])
            ->with('success', 'Configuración actualizada.');
    }
}
