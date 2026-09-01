<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ClientCollection\Commands\UpdateCheckStatusClientCollectionCommand;
use App\Modules\ClientCollection\Requests\UpdateCheckStatusClientCollectionRequest;
use App\Modules\ClientCollection\Services\ClientCollectionUpdateCheckStatusService;
use Illuminate\Http\RedirectResponse;

/**
 * El cheque avanza por su propio carril, aparte del estado del documento: por
 * eso no entra en `UpdateStatusController`.
 */
class ClientCollectionUpdateCheckStatusController extends Controller
{
    public function __construct(
        private readonly ClientCollectionUpdateCheckStatusService $updateCheckStatusService,
    ) {}

    public function __invoke(
        UpdateCheckStatusClientCollectionRequest $request,
        string $company,
        string $id,
    ): RedirectResponse {
        $this->updateCheckStatusService->execute(
            $id,
            UpdateCheckStatusClientCollectionCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('client-collections.show', ['company' => $company, 'id' => $id]);
    }
}
