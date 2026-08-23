<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseReturn\Commands\UpdateStatusPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Requests\UpdateStatusPurchaseReturnRequest;
use App\Modules\PurchaseReturn\Services\PurchaseReturnUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class PurchaseReturnUpdateStatusController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusPurchaseReturnRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusPurchaseReturnCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('purchase-returns.show', ['company' => $company, 'id' => $id]);
    }
}
