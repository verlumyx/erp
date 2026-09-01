<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Requests\UpdateStatusDispatchRequest;
use App\Modules\Dispatch\Services\DispatchUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class DispatchUpdateStatusController extends Controller
{
    public function __construct(
        private readonly DispatchUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusDispatchRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusDispatchCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('dispatches.show', ['company' => $company, 'id' => $id]);
    }
}
