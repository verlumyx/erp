<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ManualTransaction\Services\ManualTransactionCancelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManualTransactionCancelController extends Controller
{
    public function __construct(
        private readonly ManualTransactionCancelService $cancelService,
    ) {}

    public function __invoke(Request $request, string $company, string $id): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('manual-transactions.cancel') ?? false, 403);

        $this->cancelService->execute($id, session('current_company_id'));

        return redirect()
            ->route('manual-transactions.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Transacción manual cancelada correctamente.');
    }
}
