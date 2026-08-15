<?php

declare(strict_types=1);

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Commands\RenewAccountCommand;
use App\Modules\Account\Requests\RenewAccountRequest;
use App\Modules\Account\Services\AccountRenewService;
use Illuminate\Http\RedirectResponse;

class AccountRenewController extends Controller
{
    public function __construct(
        private readonly AccountRenewService $renewService,
    ) {}

    public function __invoke(RenewAccountRequest $request, string $company, string $id): RedirectResponse
    {
        $this->renewService->execute(
            RenewAccountCommand::fromRequest(
                $request,
                accountId: $id,
                companyId: session('current_company_id'),
                createdBy: (string) $request->user()->id,
            )
        );

        return redirect()
            ->route('accounts.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Renovación registrada correctamente.');
    }
}
