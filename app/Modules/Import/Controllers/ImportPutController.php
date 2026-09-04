<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Import\Commands\UpdateImportCommand;
use App\Modules\Import\Requests\UpdateImportRequest;
use App\Modules\Import\Services\ImportUpdateService;
use Illuminate\Http\RedirectResponse;

class ImportPutController extends Controller
{
    public function __construct(
        private readonly ImportUpdateService $updateService,
    ) {}

    public function __invoke(UpdateImportRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateImportCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('imports.show', ['company' => $company, 'id' => $id]);
    }
}
