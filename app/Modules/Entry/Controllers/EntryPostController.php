<?php

declare(strict_types=1);

namespace App\Modules\Entry\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Entry\Commands\CreateEntryCommand;
use App\Modules\Entry\Requests\CreateEntryRequest;
use App\Modules\Entry\Services\EntryCreateService;
use Illuminate\Http\RedirectResponse;

class EntryPostController extends Controller
{
    public function __construct(
        private readonly EntryCreateService $createService,
    ) {}

    public function __invoke(CreateEntryRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateEntryCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('entries.index', ['company' => $request->route('company')]);
    }
}
