<?php

declare(strict_types=1);

namespace App\Modules\Service\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Commands\UpdateStatusServiceCommand;
use App\Modules\Service\Requests\UpdateStatusServiceRequest;
use App\Modules\Service\Services\ServiceUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ServiceUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ServiceUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusServiceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusServiceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('services.index', ['company' => $company])
            ->with('success', 'Estado del servicio actualizado correctamente.');
    }
}
