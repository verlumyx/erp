<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientAdvance\Commands\UpdateStatusClientAdvanceCommand;
use App\Modules\ClientAdvance\Requests\UpdateStatusClientAdvanceRequest;
use App\Modules\ClientAdvance\Services\ClientAdvanceUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ClientAdvanceUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ClientAdvanceUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusClientAdvanceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusClientAdvanceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('client-advances.show', ['company' => $company, 'id' => $id]);
    }
}
