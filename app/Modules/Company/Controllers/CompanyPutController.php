<?php

declare(strict_types=1);

namespace App\Modules\Company\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Commands\UpdateCompanyCommand;
use App\Modules\Company\Requests\UpdateCompanyRequest;
use App\Modules\Company\Services\CompanyUpdateService;
use Illuminate\Http\RedirectResponse;

class CompanyPutController extends Controller
{
    public function __construct(
        private readonly CompanyUpdateService $updateService,
    ) {}

    public function __invoke(UpdateCompanyRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateCompanyCommand::fromRequest($request)
        );

        return redirect()->route('companies.show', ['company' => $company, 'id' => $id]);
    }
}
