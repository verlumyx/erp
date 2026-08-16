<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Requests\CreateSalesOrderRequest;
use App\Modules\SalesOrder\Services\SalesOrderCreateService;
use Illuminate\Http\RedirectResponse;

class SalesOrderPostController extends Controller
{
    public function __construct(
        private readonly SalesOrderCreateService $createService,
    ) {}

    public function __invoke(CreateSalesOrderRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateSalesOrderCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('sales-orders.index', ['company' => $request->route('company')]);
    }
}
