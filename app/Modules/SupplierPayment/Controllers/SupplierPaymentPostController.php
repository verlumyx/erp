<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierPayment\Commands\CreateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Requests\CreateSupplierPaymentRequest;
use App\Modules\SupplierPayment\Services\SupplierPaymentCreateService;
use Illuminate\Http\RedirectResponse;

class SupplierPaymentPostController extends Controller
{
    public function __construct(
        private readonly SupplierPaymentCreateService $createService,
    ) {}

    public function __invoke(CreateSupplierPaymentRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateSupplierPaymentCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('supplier-payments.index', ['company' => $request->route('company')]);
    }
}
