<?php

declare(strict_types=1);

namespace App\Modules\Company\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Commands\CreateCompanyCommand;
use App\Modules\Company\Requests\CreateCompanyRequest;
use App\Modules\Company\Services\CompanyCreateService;
use Illuminate\Http\RedirectResponse;

class CompanyPostController extends Controller
{
    public function __construct(
        private readonly CompanyCreateService $createService,
    ) {}

    public function __invoke(CreateCompanyRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateCompanyCommand::fromRequest($request)
        );

        return redirect()->route('companies.index', ['company' => $request->route('company')]);
    }
}
