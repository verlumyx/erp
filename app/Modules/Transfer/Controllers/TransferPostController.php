<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transfer\Commands\CreateTransferCommand;
use App\Modules\Transfer\Requests\CreateTransferRequest;
use App\Modules\Transfer\Services\TransferCreateService;
use Illuminate\Http\RedirectResponse;

class TransferPostController extends Controller
{
    public function __construct(
        private readonly TransferCreateService $createService,
    ) {}

    public function __invoke(CreateTransferRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreateTransferCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('transfers.index', ['company' => $request->route('company')]);
    }
}
