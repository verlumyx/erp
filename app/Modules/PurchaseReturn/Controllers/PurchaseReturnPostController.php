<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseReturn\Commands\CreatePurchaseReturnCommand;
use App\Modules\PurchaseReturn\Requests\CreatePurchaseReturnRequest;
use App\Modules\PurchaseReturn\Services\PurchaseReturnCreateService;
use Illuminate\Http\RedirectResponse;

class PurchaseReturnPostController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnCreateService $createService,
    ) {}

    public function __invoke(CreatePurchaseReturnRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreatePurchaseReturnCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('purchase-returns.index', ['company' => $request->route('company')]);
    }
}
