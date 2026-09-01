<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesReturn\Commands\CreateSalesReturnCommand;
use App\Modules\SalesReturn\Requests\CreateSalesReturnRequest;
use App\Modules\SalesReturn\Services\SalesReturnCreateService;
use Illuminate\Http\RedirectResponse;

class SalesReturnPostController extends Controller
{
    public function __construct(
        private readonly SalesReturnCreateService $createService,
    ) {}

    public function __invoke(CreateSalesReturnRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateSalesReturnCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('sales-returns.index', ['company' => $request->route('company')]);
    }
}
