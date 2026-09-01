<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientCollection\Commands\UpdateClientCollectionCommand;
use App\Modules\ClientCollection\Requests\UpdateClientCollectionRequest;
use App\Modules\ClientCollection\Services\ClientCollectionUpdateService;
use Illuminate\Http\RedirectResponse;

class ClientCollectionPutController extends Controller
{
    public function __construct(
        private readonly ClientCollectionUpdateService $updateService,
    ) {}

    public function __invoke(UpdateClientCollectionRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateClientCollectionCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('client-collections.show', ['company' => $company, 'id' => $id]);
    }
}
