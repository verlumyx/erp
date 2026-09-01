<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientCollection\Commands\CreateClientCollectionCommand;
use App\Modules\ClientCollection\Requests\CreateClientCollectionRequest;
use App\Modules\ClientCollection\Services\ClientCollectionCreateService;
use Illuminate\Http\RedirectResponse;

class ClientCollectionPostController extends Controller
{
    public function __construct(
        private readonly ClientCollectionCreateService $createService,
    ) {}

    public function __invoke(CreateClientCollectionRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateClientCollectionCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('client-collections.index', ['company' => $request->route('company')]);
    }
}
