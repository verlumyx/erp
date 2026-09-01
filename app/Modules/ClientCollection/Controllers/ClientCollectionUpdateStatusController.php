<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientCollection\Commands\UpdateStatusClientCollectionCommand;
use App\Modules\ClientCollection\Requests\UpdateStatusClientCollectionRequest;
use App\Modules\ClientCollection\Services\ClientCollectionUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ClientCollectionUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ClientCollectionUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusClientCollectionRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusClientCollectionCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('client-collections.show', ['company' => $company, 'id' => $id]);
    }
}
