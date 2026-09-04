<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Import\Commands\UpdateStatusImportCommand;
use App\Modules\Import\Requests\UpdateStatusImportRequest;
use App\Modules\Import\Services\ImportUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class ImportUpdateStatusController extends Controller
{
    public function __construct(
        private readonly ImportUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusImportRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusImportCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('imports.show', ['company' => $company, 'id' => $id]);
    }
}
