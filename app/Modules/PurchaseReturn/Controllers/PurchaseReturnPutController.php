<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseReturn\Commands\UpdatePurchaseReturnCommand;
use App\Modules\PurchaseReturn\Requests\UpdatePurchaseReturnRequest;
use App\Modules\PurchaseReturn\Services\PurchaseReturnUpdateService;
use Illuminate\Http\RedirectResponse;

class PurchaseReturnPutController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnUpdateService $updateService,
    ) {}

    public function __invoke(UpdatePurchaseReturnRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdatePurchaseReturnCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('purchase-returns.show', ['company' => $company, 'id' => $id]);
    }
}
