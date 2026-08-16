<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientType\Commands\UpdateStatusClientTypeCommand;
use App\Modules\ClientType\Requests\UpdateStatusClientTypeRequest;
use App\Modules\ClientType\Services\ClientTypeUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ClientTypeUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ClientTypeUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusClientTypeRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusClientTypeCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('client-types.index', ['company' => $company])
            ->with('success', 'Estado del tipo de cliente actualizado correctamente.');
    }
}
