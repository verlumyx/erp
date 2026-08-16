<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientType\Commands\CreateClientTypeCommand;
use App\Modules\ClientType\Requests\CreateClientTypeRequest;
use App\Modules\ClientType\Services\ClientTypeCreateService;
use Illuminate\Http\RedirectResponse;

class ClientTypePostController extends Controller
{
    public function __construct(
        private readonly ClientTypeCreateService $createService,
    ) {}

    public function __invoke(CreateClientTypeRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateClientTypeCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('client-types.index', ['company' => $request->route('company')]);
    }
}
