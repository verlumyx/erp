<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesOrder\Commands\UpdateSalesOrderCommand;
use App\Modules\SalesOrder\Requests\UpdateSalesOrderRequest;
use App\Modules\SalesOrder\Services\SalesOrderUpdateService;
use Illuminate\Http\RedirectResponse;

class SalesOrderPutController extends Controller
{
    public function __construct(
        private readonly SalesOrderUpdateService $updateService,
    ) {}

    public function __invoke(UpdateSalesOrderRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateSalesOrderCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('sales-orders.show', ['company' => $company, 'id' => $id]);
    }
}
