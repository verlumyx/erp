<?php

declare(strict_types=1);

namespace App\Modules\Tax\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tax\Commands\CreateTaxCommand;
use App\Modules\Tax\Requests\CreateTaxRequest;
use App\Modules\Tax\Services\TaxCreateService;
use Illuminate\Http\RedirectResponse;

class TaxPostController extends Controller
{
    public function __construct(
        private readonly TaxCreateService $createService,
    ) {}

    public function __invoke(CreateTaxRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateTaxCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('taxes.index', ['company' => $request->route('company')]);
    }
}
