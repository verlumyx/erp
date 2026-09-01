<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesReturn\Commands\UpdateSalesReturnCommand;
use App\Modules\SalesReturn\Requests\UpdateSalesReturnRequest;
use App\Modules\SalesReturn\Services\SalesReturnUpdateService;
use Illuminate\Http\RedirectResponse;

class SalesReturnPutController extends Controller
{
    public function __construct(
        private readonly SalesReturnUpdateService $updateService,
    ) {}

    public function __invoke(UpdateSalesReturnRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateSalesReturnCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('sales-returns.show', ['company' => $company, 'id' => $id]);
    }
}
