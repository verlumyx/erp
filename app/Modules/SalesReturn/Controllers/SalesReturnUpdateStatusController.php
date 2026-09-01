<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SalesReturn\Commands\UpdateStatusSalesReturnCommand;
use App\Modules\SalesReturn\Requests\UpdateStatusSalesReturnRequest;
use App\Modules\SalesReturn\Services\SalesReturnUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class SalesReturnUpdateStatusController extends Controller
{
    public function __construct(
        private readonly SalesReturnUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusSalesReturnRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusSalesReturnCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('sales-returns.show', ['company' => $company, 'id' => $id]);
    }
}
