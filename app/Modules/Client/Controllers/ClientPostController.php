<?php

declare(strict_types=1);

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Commands\CreateClientCommand;
use App\Modules\Client\Requests\CreateClientRequest;
use App\Modules\Client\Services\ClientCreateService;
use Illuminate\Http\RedirectResponse;

class ClientPostController extends Controller
{
    public function __construct(
        private readonly ClientCreateService $createService,
    ) {}

    public function __invoke(CreateClientRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateClientCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('clients.index', ['company' => $request->route('company')]);
    }
}
