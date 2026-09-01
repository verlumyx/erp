<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientAdvance\Commands\CreateClientAdvanceCommand;
use App\Modules\ClientAdvance\Requests\CreateClientAdvanceRequest;
use App\Modules\ClientAdvance\Services\ClientAdvanceCreateService;
use Illuminate\Http\RedirectResponse;

class ClientAdvancePostController extends Controller
{
    public function __construct(
        private readonly ClientAdvanceCreateService $createService,
    ) {}

    public function __invoke(CreateClientAdvanceRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateClientAdvanceCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('client-advances.index', ['company' => $request->route('company')]);
    }
}
