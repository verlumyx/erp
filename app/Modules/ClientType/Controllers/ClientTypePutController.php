<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientType\Commands\UpdateClientTypeCommand;
use App\Modules\ClientType\Requests\UpdateClientTypeRequest;
use App\Modules\ClientType\Services\ClientTypeUpdateService;
use Illuminate\Http\RedirectResponse;

class ClientTypePutController extends Controller
{
    public function __construct(
        private readonly ClientTypeUpdateService $updateService,
    ) {}

    public function __invoke(UpdateClientTypeRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateClientTypeCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('client-types.show', ['company' => $company, 'id' => $id]);
    }
}
