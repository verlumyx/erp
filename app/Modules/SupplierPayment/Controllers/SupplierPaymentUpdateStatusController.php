<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SupplierPayment\Commands\UpdateStatusSupplierPaymentCommand;
use App\Modules\SupplierPayment\Requests\UpdateStatusSupplierPaymentRequest;
use App\Modules\SupplierPayment\Services\SupplierPaymentUpdateStatusService;
use Illuminate\Http\RedirectResponse;

class SupplierPaymentUpdateStatusController extends Controller
{
    public function __construct(
        private readonly SupplierPaymentUpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatusSupplierPaymentRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatusSupplierPaymentCommand::fromRequest($request),
            $company,
        );

        return redirect()->route('supplier-payments.show', ['company' => $company, 'id' => $id]);
    }
}
