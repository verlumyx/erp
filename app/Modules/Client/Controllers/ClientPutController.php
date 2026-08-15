<?php

declare(strict_types=1);

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Commands\UpdateClientCommand;
use App\Modules\Client\Requests\UpdateClientRequest;
use App\Modules\Client\Services\ClientUpdateService;
use Illuminate\Http\RedirectResponse;

class ClientPutController extends Controller
{
    public function __construct(
        private readonly ClientUpdateService $updateService,
    ) {}

    public function __invoke(UpdateClientRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateClientCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('clients.show', ['company' => $company, 'id' => $id]);
    }
}
