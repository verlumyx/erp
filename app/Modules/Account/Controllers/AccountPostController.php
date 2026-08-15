<?php

declare(strict_types=1);

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Commands\CreateAccountCommand;
use App\Modules\Account\Requests\CreateAccountRequest;
use App\Modules\Account\Services\AccountCreateService;
use Illuminate\Http\RedirectResponse;

class AccountPostController extends Controller
{
    public function __construct(
        private readonly AccountCreateService $createService,
    ) {}

    public function __invoke(CreateAccountRequest $request): RedirectResponse
    {
        $account = $this->createService->execute(
            CreateAccountCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()
            ->route('accounts.show', ['company' => $request->route('company'), 'id' => $account->id])
            ->with('success', 'Cuenta creada correctamente.');
    }
}
