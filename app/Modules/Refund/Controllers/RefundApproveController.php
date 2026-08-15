<?php

declare(strict_types=1);

namespace App\Modules\Refund\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Refund\Commands\ResolveRefundCommand;
use App\Modules\Refund\Services\RefundApproveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RefundApproveController extends Controller
{
    public function __construct(
        private readonly RefundApproveService $approveService,
    ) {}

    public function __invoke(Request $request, string $company, string $id): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('refunds.approve') ?? false, 403);

        $this->approveService->execute(new ResolveRefundCommand(
            refundId: $id,
            companyId: session('current_company_id'),
            resolvedBy: (string) $request->user()->id,
        ));

        return redirect()
            ->route('refunds.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Reembolso aprobado correctamente.');
    }
}
