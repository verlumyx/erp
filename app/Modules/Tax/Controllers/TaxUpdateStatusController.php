<?php

declare(strict_types=1);

namespace App\Modules\Tax\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tax\Commands\UpdateStatusTaxCommand;
use App\Modules\Tax\Requests\UpdateStatusTaxRequest;
use App\Modules\Tax\Services\TaxUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class TaxUpdateStatusController extends Controller
{
    public function __construct(
        private readonly TaxUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusTaxRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusTaxCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('taxes.index', ['company' => $company])
            ->with('success', 'Estado del impuesto actualizado correctamente.');
    }
}
