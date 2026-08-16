<?php

declare(strict_types=1);

namespace App\Modules\Tax\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tax\Commands\UpdateTaxCommand;
use App\Modules\Tax\Requests\UpdateTaxRequest;
use App\Modules\Tax\Services\TaxUpdateService;
use Illuminate\Http\RedirectResponse;

class TaxPutController extends Controller
{
    public function __construct(
        private readonly TaxUpdateService $updateService,
    ) {}

    public function __invoke(UpdateTaxRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateTaxCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('taxes.show', ['company' => $company, 'id' => $id]);
    }
}
