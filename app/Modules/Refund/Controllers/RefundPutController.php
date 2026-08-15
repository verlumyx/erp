<?php

declare(strict_types=1);

namespace App\Modules\Refund\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Refund\Commands\UpdateRefundCommand;
use App\Modules\Refund\Requests\UpdateRefundRequest;
use App\Modules\Refund\Services\RefundUpdateService;
use Illuminate\Http\RedirectResponse;

class RefundPutController extends Controller
{
    public function __construct(
        private readonly RefundUpdateService $updateService,
    ) {}

    public function __invoke(UpdateRefundRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateRefundCommand::fromRequest($request),
            session('current_company_id'),
        );

        return redirect()
            ->route('refunds.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Reembolso actualizado correctamente.');
    }
}
