<?php

declare(strict_types=1);

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sale\Commands\CancelSaleCommand;
use App\Modules\Sale\Requests\CancelSaleRequest;
use App\Modules\Sale\Services\SaleCancelService;
use Illuminate\Http\RedirectResponse;

class SaleCancelController extends Controller
{
    public function __construct(
        private readonly SaleCancelService $cancelService,
    ) {}

    public function __invoke(CancelSaleRequest $request, string $company, string $id): RedirectResponse
    {
        $this->cancelService->execute(
            CancelSaleCommand::fromRequest(
                $request,
                saleId: $id,
                companyId: session('current_company_id'),
            )
        );

        return redirect()
            ->route('sales.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Venta expulsada correctamente.');
    }
}
