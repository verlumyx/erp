<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseOrder\Commands\UpdatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Requests\UpdatePurchaseOrderRequest;
use App\Modules\PurchaseOrder\Services\PurchaseOrderUpdateService;
use Illuminate\Http\RedirectResponse;

class PurchaseOrderPutController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderUpdateService $updateService,
    ) {}

    public function __invoke(UpdatePurchaseOrderRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdatePurchaseOrderCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('purchase-orders.show', ['company' => $company, 'id' => $id]);
    }
}
