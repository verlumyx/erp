<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierPayment\Commands\UpdateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Requests\UpdateSupplierPaymentRequest;
use App\Modules\SupplierPayment\Services\SupplierPaymentUpdateService;
use Illuminate\Http\RedirectResponse;

class SupplierPaymentPutController extends Controller
{
    public function __construct(
        private readonly SupplierPaymentUpdateService $updateService,
    ) {}

    public function __invoke(UpdateSupplierPaymentRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateSupplierPaymentCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('supplier-payments.show', ['company' => $company, 'id' => $id]);
    }
}
