<?php

declare(strict_types=1);

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Commands\UpdateAccountCommand;
use App\Modules\Account\Requests\UpdateAccountRequest;
use App\Modules\Account\Services\AccountUpdateService;
use Illuminate\Http\RedirectResponse;

class AccountPutController extends Controller
{
    public function __construct(
        private readonly AccountUpdateService $updateService,
    ) {}

    public function __invoke(UpdateAccountRequest $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            UpdateAccountCommand::fromRequest($request),
            $company,
        );

        return redirect()
            ->route('accounts.show', ['company' => $company, 'id' => $id])
            ->with('success', 'Cuenta actualizada correctamente.');
    }
}
