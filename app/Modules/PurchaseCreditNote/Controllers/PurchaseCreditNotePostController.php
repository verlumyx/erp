<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PurchaseCreditNote\Commands\CreatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Requests\CreatePurchaseCreditNoteRequest;
use App\Modules\PurchaseCreditNote\Services\PurchaseCreditNoteCreateService;
use Illuminate\Http\RedirectResponse;

class PurchaseCreditNotePostController extends Controller
{
    public function __construct(
        private readonly PurchaseCreditNoteCreateService $createService,
    ) {}

    public function __invoke(CreatePurchaseCreditNoteRequest $request): RedirectResponse
    {
        $this->createService->execute(
            CreatePurchaseCreditNoteCommand::fromRequest($request, session('current_company_id'))
        );

        return redirect()->route('purchase-credit-notes.index', ['company' => $request->route('company')]);
    }
}
