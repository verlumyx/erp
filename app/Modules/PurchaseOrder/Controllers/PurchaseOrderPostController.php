<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseOrder\Commands\CreatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Requests\CreatePurchaseOrderRequest;
use App\Modules\PurchaseOrder\Services\PurchaseOrderCreateService;
use Illuminate\Http\RedirectResponse;

class PurchaseOrderPostController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderCreateService $createService,
    ) {}

    public function __invoke(CreatePurchaseOrderRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreatePurchaseOrderCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('purchase-orders.index', ['company' => $request->route('company')]);
    }
}
