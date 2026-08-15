<?php

declare(strict_types=1);

namespace App\Modules\Refund\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Refund\Commands\ResolveRefundCommand;
use App\Modules\Refund\Services\RefundRejectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RefundRejectController extends Controller
{
    public function __construct(
        private readonly RefundRejectService $rejectService,
    ) {}

    public function __invoke(Request $request, string $company, string $id): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('refunds.reject') ?? false, 403);

        $this->rejectService->execute(new ResolveRefundCommand(
            refundId: $id,
            companyId: session('current_company_id'),
            resolvedBy: (string) $request->user()->id,
        ));

        return redirect()
            ->route('refunds.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Reembolso rechazado correctamente.');
    }
}
