<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesOrder\Commands\UpdateStatusSalesOrderCommand;
use App\Modules\SalesOrder\Requests\UpdateStatusSalesOrderRequest;
use App\Modules\SalesOrder\Services\SalesOrderUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class SalesOrderUpdateStatusController extends Controller
{
    public function __construct(
        private readonly SalesOrderUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusSalesOrderRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusSalesOrderCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('sales-orders.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Estado del pedido actualizado correctamente.');
    }
}
