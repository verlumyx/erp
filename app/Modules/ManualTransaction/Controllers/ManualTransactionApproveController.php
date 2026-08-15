<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ManualTransaction\Services\ManualTransactionApproveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManualTransactionApproveController extends Controller
{
    public function __construct(
        private readonly ManualTransactionApproveService $approveService,
    ) {}

    public function __invoke(Request $request, string $company, string $id): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('manual-transactions.approve') ?? false, 403);

        $this->approveService->execute(
            $id,
            session('current_company_id'),
            (string) $request->user()->id,
        );

        return redirect()
            ->route('manual-transactions.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Transacción manual aprobada correctamente.');
    }
}
