<?php

declare(strict_types=1);

namespace App\Modules\Company\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Commands\UpdateCompanyMenusCommand;
use App\Modules\Company\Requests\UpdateCompanyMenusRequest;
use App\Modules\Company\Services\CompanyMenusUpdateService;
use Illuminate\Http\RedirectResponse;

class CompanyMenuUpdateController extends Controller
{
    public function __construct(
        private readonly CompanyMenusUpdateService $updateService,
    ) {}

    public function __invoke(UpdateCompanyMenusRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateCompanyMenusCommand::fromRequest($request)
        );

        return redirect()->route('companies.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Menús de la empresa actualizados correctamente.');
    }
}
