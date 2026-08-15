<?php

declare(strict_types=1);

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Commands\UpdateStatusClientCommand;
use App\Modules\Client\Requests\UpdateStatusClientRequest;
use App\Modules\Client\Services\ClientUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ClientUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ClientUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusClientRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusClientCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('clients.index', ['company' => $company])
            ->with('success', 'Estado del cliente actualizado correctamente.');
    }
}
