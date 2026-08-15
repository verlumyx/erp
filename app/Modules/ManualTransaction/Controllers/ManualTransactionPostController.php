<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ManualTransaction\Commands\CreateManualTransactionCommand;
use App\Modules\ManualTransaction\Requests\CreateManualTransactionRequest;
use App\Modules\ManualTransaction\Services\ManualTransactionCreateService;
use Illuminate\Http\RedirectResponse;

class ManualTransactionPostController extends Controller
{
    public function __construct(
        private readonly ManualTransactionCreateService $createService,
    ) {}

    public function __invoke(CreateManualTransactionRequest $request): RedirectResponse
    {
        $manualTransaction = $this->createService->execute(
            CreateManualTransactionCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()
            ->route('manual-transactions.show', ['company' => $request->route('company'), 'id' => $manualTransaction->id])
            ->with('success', 'Transacción manual registrada correctamente.');
    }
}
