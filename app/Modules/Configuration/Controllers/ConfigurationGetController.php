<?php

declare(strict_types=1);

namespace App\Modules\Configuration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Configuration\Resources\ConfigurationResource;
use App\Modules\Configuration\Services\ConfigurationFindService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La configuración es un singleton por empresa: no tiene índice ni pantalla de
 * creación, se entra directo a editarla.
 */
class ConfigurationGetController extends Controller
{
    public function __construct(
        private readonly ConfigurationFindService $findService,
    ) {}

    public function edit(string $company): Response
    {
        abort_unless(request()->user()?->hasPermission('configuration.show') ?? false, 403);

        $configuration = $this->findService->execute($company);

        return Inertia::render('configuration/edit', [
            'configuration' => (new ConfigurationResource($configuration))->resolve(),
        ]);
    }
}
