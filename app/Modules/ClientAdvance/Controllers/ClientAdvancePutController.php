<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientAdvance\Commands\UpdateClientAdvanceCommand;
use App\Modules\ClientAdvance\Requests\UpdateClientAdvanceRequest;
use App\Modules\ClientAdvance\Services\ClientAdvanceUpdateService;
use Illuminate\Http\RedirectResponse;

class ClientAdvancePutController extends Controller
{
    public function __construct(
        private readonly ClientAdvanceUpdateService $updateService,
    ) {}

    public function __invoke(UpdateClientAdvanceRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateClientAdvanceCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('client-advances.show', ['company' => $company, 'id' => $id]);
    }
}
