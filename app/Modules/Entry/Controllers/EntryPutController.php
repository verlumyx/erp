<?php

declare(strict_types=1);

namespace App\Modules\Entry\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Entry\Commands\UpdateEntryCommand;
use App\Modules\Entry\Requests\UpdateEntryRequest;
use App\Modules\Entry\Services\EntryUpdateService;
use Illuminate\Http\RedirectResponse;

class EntryPutController extends Controller
{
    public function __construct(
        private readonly EntryUpdateService $updateService,
    ) {}

    public function __invoke(UpdateEntryRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateEntryCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('entries.show', ['company' => $company, 'id' => $id]);
    }
}
