<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Import\Commands\CreateImportCommand;
use App\Modules\Import\Requests\CreateImportRequest;
use App\Modules\Import\Services\ImportCreateService;
use Illuminate\Http\RedirectResponse;

class ImportPostController extends Controller
{
    public function __construct(
        private readonly ImportCreateService $createService,
    ) {}

    public function __invoke(CreateImportRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateImportCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('imports.index', ['company' => $request->route('company')]);
    }
}
