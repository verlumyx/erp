<?php

declare(strict_types=1);

namespace App\Modules\Entry\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Entry\Commands\UpdateStatusEntryCommand;
use App\Modules\Entry\Requests\UpdateStatusEntryRequest;
use App\Modules\Entry\Services\EntryUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class EntryUpdateStatusController extends Controller
{
    public function __construct(
        private readonly EntryUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusEntryRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusEntryCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('entries.show', ['company' => $company, 'id' => $id]);
    }
}
