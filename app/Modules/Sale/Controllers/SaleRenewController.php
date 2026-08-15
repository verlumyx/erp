<?php

declare(strict_types=1);

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sale\Commands\RenewSaleCommand;
use App\Modules\Sale\Requests\RenewSaleRequest;
use App\Modules\Sale\Services\SaleRenewService;
use Illuminate\Http\RedirectResponse;

class SaleRenewController extends Controller
{
    public function __construct(
        private readonly SaleRenewService $renewService,
    ) {}

    public function __invoke(RenewSaleRequest $request, string $company, string $id): RedirectResponse
    {
        $this->renewService->execute(
            RenewSaleCommand::fromRequest(
                $request,
                saleId: $id,
                companyId: session('current_company_id'),
                renewedBy: (string) $request->user()->id,
            )
        );

        return redirect()
            ->route('sales.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Renovación registrada correctamente.');
    }
}
