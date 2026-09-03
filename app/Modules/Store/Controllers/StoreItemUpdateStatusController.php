<?php

declare(strict_types=1);

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Commands\UpdateStatusStoreItemCommand;
use App\Modules\Store\Requests\UpdateStatusStoreItemRequest;
use App\Modules\Store\Services\StoreItemUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class StoreItemUpdateStatusController extends Controller
{
    public function __construct(
        private readonly StoreItemUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusStoreItemRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusStoreItemCommand::fromRequest($request),
            $company,
        );

        return back()->with('success', 'Estado de la publicación actualizado.');
    }
}
