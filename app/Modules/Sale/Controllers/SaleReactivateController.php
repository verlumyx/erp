<?php

declare(strict_types=1);

namespace App\Modules\Sale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sale\Commands\ReactivateSaleCommand;
use App\Modules\Sale\Requests\ReactivateSaleRequest;
use App\Modules\Sale\Services\SaleReactivateService;
use Illuminate\Http\RedirectResponse;

class SaleReactivateController extends Controller
{
    public function __construct(
        private readonly SaleReactivateService $reactivateService,
    ) {}

    /**
     * Reactiva una venta. Si los profiles originales ya están ocupados y no se
     * envían reemplazos, el servicio lanza ProfilesUnavailableException (HTTP 409).
     */
    public function __invoke(ReactivateSaleRequest $request, string $company, string $id): RedirectResponse
    {
        $this->reactivateService->execute(
            ReactivateSaleCommand::fromRequest(
                $request,
                saleId: $id,
                companyId: session('current_company_id'),
                renewedBy: (string) $request->user()->id,
            )
        );

        return redirect()
            ->route('sales.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Venta reactivada correctamente.');
    }
}
