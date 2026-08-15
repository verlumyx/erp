<?php

declare(strict_types=1);

namespace App\Modules\Company\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Commands\UpdateStatusCompanyCommand;
use App\Modules\Company\Requests\UpdateStatusCompanyRequest;
use App\Modules\Company\Services\CompanyUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class CompanyUpdateStatusController extends Controller
{
    public function __construct(
        private readonly CompanyUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusCompanyRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusCompanyCommand::fromRequest($request)
        );

        return redirect()->route('companies.index', ['company' => $company])
            ->with('success', 'Estado de la empresa actualizado correctamente.');
    }
}
